/*
 * auth-jwt-node.js
 */

module.exports = function (app) {
    var db  = app.locals.db;
    var conf = app.locals.conf;

    var expressJwt = require('express-jwt');
    var jwt = require('jsonwebtoken');
    var unless = require('express-unless');
    var crypto = require('crypto');
    var debug = require('debug')('app:routes:auth:' + process.pid);
    var cookieParser = require('cookie-parser')
    //var bodyParser = require('body-parser');
    //app.use(bodyParser.urlencoded());

    var middleware = function () {
        var func = function (req, res, next) {
            var token = fetch(req.headers) || req.cookies.token;
            if (token === null && conf.jwt.required === false) {
                req.user = {};
                next();
                return;
            }
            jwt.verify(token, conf.jwt.secret, function (err, decode) {
                if (err) {
                    if (conf.jwt.required === false ) {
                        req.user = {};
                        next();
                        return;
                    }
                    req.user = undefined;
                    return res.status(401).json('401 Unauthorized (invalid token and authentication is required)');
                }
                db.read([decode._id], function (err, user) {
                    // we could add decode properties to the user object here
                    req.user = user[0];
                    next();
                });
            });
        };
        func.unless = require('express-unless');
        return func;
    };

    app.use(cookieParser())
    app.use('/api', middleware().unless({path:['/api/signIn']}));

    //var jwtMiddleware = expressJwt({secret:conf.jwt.secret});
    //jwtMiddleware.unless = unless;
    //app.use('/api', jwtMiddleware.unless({path:['/api/signIn']}));

    var authenticate = function (req, res, next) {
        debug('Processing authenticate middleware');
        if (!req.body.username || !req.body.password) {
            debug('no username or password');
            return res.status(401).json('Invalid username or password');
        }
        db.search({'username': req.body.username}, function (err, doc) {
            if (err || doc.length === 0) {
                return res.status(401).json('Invalid username or password');
            }
            db.read([doc[0]], function (err, user) {
                user = user[0];
                if (crypto.createHash(conf.jwt.passwordHashAlgorithm).update(req.body.password).digest('hex') !== user.password) {
                    return res.status(401).json('Invalid username or password');
                }
                debug('User authenticated, generating token');
                user.lastlogin = Date.now();
                db.update([user], function(err, nodes){
                    user.token = jwt.sign({ _id: user._id, username: user.username }, conf.jwt.secret, { expiresIn: conf.jwt.exp*60 });
                    var decoded = jwt.decode(user.token);
                    user.token_exp = decoded.exp;
                    user.token_iat = decoded.iat;
                    delete user.password;
                    debug('Token generated for user: %s, token: %s', user.username, user.token);
                    req.user = user;
                    req.user.address = req.connection.remoteAddress;
                    req.user.class = req.user.class || 'guest';
                    return res.status(200).json(req.user);
                });
            });
        });
    };

    var fetch = function (headers) {
        if (headers && headers.authorization) {
            var authorization = headers.authorization;
            var part = authorization.split(' ');
            if (part.length === 2) {
                var token = part[1];
                return part[1];
            } else {
                return null;
            }
        } else {
            return null;
        }
    };

    // error handler for all the applications
/*
    app.use(function (err, req, res, next) {
        var errorType = typeof err,
            code = 500,
            msg = { message: 'Internal Server Error' };

        switch (err.name) {
            case 'UnauthorizedError':
                code = err.status;
                msg = undefined;
                break;
            case 'BadRequestError':
            case 'UnauthorizedAccessError':
            case 'NotFoundError':
                code = err.status;
                msg = err.inner;
                break;
            default:
                break;
        }
        console.log(err.name);
        return res.status(code).json(msg);
    });
*/

    app.get('/api/verify', function (req, res) {
        return res.status(200).json(req.user);
/* This is useless ! processed by the middleware - double check before removing it
        var token = fetch(req.headers);
        if (conf.jwt.required === false ) {
            return res.status(200).json(req.user);
        }
        jwt.verify(token, conf.jwt.secret, function (err, decode) {
            if (err) {
                req.user = undefined;
                return res.status(401).send('invalid token');
            }
            return res.status(200).json(req.user);
        });
*/
    });

    app.route('/api/signIn').post(authenticate, function (req, res, next) {
        return res.status(200).json(req.user);
    });
}
