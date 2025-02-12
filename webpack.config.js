const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('src/Resources/public/logs/')
    .setPublicPath('/bundles/alpdeskcore/logs')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(false)
    .addEntry('alpdeskcore_logs', './assets/alpdeskcore_logs.js')
;

const alpdeskCoreLogs = Encore.getWebpackConfig();

Encore.reset();

Encore
    .setOutputPath('src/Resources/public/database/')
    .setPublicPath('/bundles/alpdeskcore/database')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(false)
    .addEntry('alpdeskcore_widget_databasemanager', './assets/alpdeskcore_widget_databasemanager.js')
;

const alpdeskCoreDatabaseManager = Encore.getWebpackConfig();

module.exports = [alpdeskCoreLogs, alpdeskCoreDatabaseManager];