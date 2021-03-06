/*
 * index.js - from Damas-Core
 * Licensed under the GNU GPL v3
 */

/*
 * Initialize Express
 */
var debug = require('debug')('app:' + process.pid);

debug('Initializing express');
var express = require('express');
var app = express();

/*
 * Configuration
 */
debug('Loading configuration');

var conf = app.locals.conf = require('./conf');
app.locals.db = require('./db')(conf.db, conf[conf.db]);

require('./routes')(app, express);


/*
 * Export the app if we are in a test environment
 */
if (module.parent) {
    module.exports = app;
    return;
}

debug('Working in %s mode', app.get('env'));
var socket = require('./events/socket');


/*
 * Create a HTTP server
 */
var http_port = process.env.HTTP_PORT || 8090;
debug('Creating HTTP server on port %s', http_port);
var http = require('http').createServer(app).listen(http_port, function () {
    debug('HTTP server listening on port %s', http_port);
    socket.attach(http);
});

/*
 * Create a HTTPS server if there are certificates
 */
if (conf.connection && conf.connection.Key && conf.connection.Cert) {
    var fs = require('fs');
    var https_port = process.env.HTTPS_PORT || 8443;
    debug('Creating HTTPS server on port %s', https_port);
    var https = require('https').createServer({
        key: fs.readFileSync(conf.connection.Key).toString(),
        cert: fs.readFileSync(conf.connection.Cert).toString()
    }, app).listen(https_port, function () {
        debug('HTTPS server listening on port %s', https_port);
        socket.attach(https);
    });
}


