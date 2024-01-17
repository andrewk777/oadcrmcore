export const CONF = {
    APP_URL: process.env.MIX_APP_URL,
    API_URL: process.env.MIX_APP_URL + '/api',
    APP_LOGIN_URL: '/',
    APP_DEFAULT_URL: '{!! $appDefaultUrl !!}',
    APP_NAME: process.env.MIX_APP_NAME,
    PRIMARY_COLOR: '#564AA3',
    DF_MOMENTS: 'D MMM, YYYY',
    DB_MOMENTS: 'YYYY-MM-DD',
    DB_MOMENTS_TIME: 'YYYY-MM-DD HH:mm:00',
    MOMENT_TIME_HUMAN: 'h:mmA',
    MOMENT_DATETIME_HUMAN: 'D MMM, YYYY h:mmA'
}