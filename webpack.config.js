const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('src/Resources/public/logs/')
    .setPublicPath('/bundles/alpdeskcore/logs')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(false)
    .addEntry('alpdeskcore_logs', './assets/alpdeskcore_logs.js')
    .addStyleEntry('alpdeskcore_logs_css', './assets/alpdeskcore_logs.css')
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
    .addStyleEntry('alpdeskcore_widget_databasemanager_css', './assets/alpdeskcore_widget_databasemanager.css')
;

const alpdeskCoreDatabaseManager = Encore.getWebpackConfig();

module.exports = [alpdeskCoreLogs, alpdeskCoreDatabaseManager];