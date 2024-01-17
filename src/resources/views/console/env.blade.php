APP_NAME="{{ $app_name }}"
APP_ENV={{ $environment }}
CLIENT="{{ $client_name }}"
DEVELOPER="{{ $developer_name }}"
DEVELOPER_EMAIL="{{ $developer_email }}"
APP_DOMAIN={{ $app_url }}
APP_URL=http://{{ $app_url }}
SESSION_DOMAIN={{ $session_domain }}
SANCTUM_STATEFUL_DOMAINS={{ $app_url }}

APP_DEBUG={{ $debug }}

MIX_APP_URL=${APP_URL}
MIX_APP_NAME="${APP_NAME}"
APP_KEY=

STRIPE_PUBLISHABLE_KEY={{ $stripe_publishable_key }}
STRIPE_SECRET_KEY={{ $stripe_secret_key }}
MIX_STRIPE_PUBLISHABLE_KEY="${STRIPE_PUBLISHABLE_KEY}"

MAILGUN_DOMAIN={{ $mailgun_domain }}
MAILGUN_API_KEY={{ $mailgun_key }}
MAILGUN_SECRET={{ $mailgun_secret }}

LOG_CHANNEL=rollbar
LOG_LEVEL=debug 

DB_CONNECTION=mysql
DB_HOST={{ $db_host }}
DB_PORT={{ $db_port }}
DB_DATABASE={{ $db_name }}
DB_USERNAME={{ $db_username }}
DB_PASSWORD="{{ $db_password }}"

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=cookie
SESSION_LIFETIME=1440

MAIL_MAILER=mailgun
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=25
MAIL_USERNAME={{ $mail_gun_user }}
MAIL_PASSWORD={{ $mail_gun_pass }}
MAIL_ENCRYPTION=TLS
MAIL_FROM_ADDRESS=notification@${APP_DOMAIN}
MAIL_FROM_NAME="${APP_NAME}"

BACKUP_DRVR=ftp
BACKUP_HOST={{ $backup_host }}
BACKUP_USER={{ $backup_user }}
BACKUP_PASS={{ $backup_pass }}
BACKUP_FLDR=${APP_URL}

ROLLBAR_TOKEN={{ $rollbar_token }}
MIX_ROLLBACK_TOKEN_JS={{ $rollback_token_js }}
MIX_ROLLBACK_ENV="${APP_ENV}"

RINGCENTRAL_ID={{ $ringcentral_id }}
RINGCENTRAL_SECRET={{ $ringcentral_secret }}
RINGCENTRAL_REDIRECT=/RingCentralAuthCallback
RINGCENTRAL_URL={{ $ringcentral_url }}
RINGCENTRAL_USERNAME={{ $ringcentral_username }}
RINGCENTRAL_PASSWORD={{ $ringcentral_password }}
