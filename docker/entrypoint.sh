#!/bin/sh
# msmtp beállítás környezeti változókból — ha nincs SMTP megadva, a mail()
# csendben elbukik, de a foglalás akkor is a data/foglalasok.log-ba kerül.
set -e

if [ -n "$SMTP_HOST" ]; then
  cat > /etc/msmtprc <<EOF
defaults
auth on
tls on
tls_trust_file /etc/ssl/certs/ca-certificates.crt
logfile /dev/stderr

account default
host $SMTP_HOST
port ${SMTP_PORT:-587}
user $SMTP_USER
password $SMTP_PASS
from ${SMTP_FROM:-noreply@localhost}
EOF
  chmod 600 /etc/msmtprc
fi

exec "$@"
