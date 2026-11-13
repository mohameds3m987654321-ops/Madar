#!/usr/bin/env bash
set -e

# تشغيل أوامر لارافيل هنا لأن البيئة وقاعدة البيانات أصبحت جاهزة
php artisan package:discover --ansi
php artisan storage:link --force

# تنفيذ الأمر الأساسي (تشغيل Supervisord)
exec "$@"