<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$routes = app('router')->getRoutes();
$names = ['home','login','users.create','verification.notice','users.profile','search','courses.index','courses.show','courses.my','lessons.show','quizzes.show','quizzes.result','courses.certificate','teacher.dashboard','courses.create','courses.edit','lessons.manage','lessons.create','lessons.create-for-course','lessons.edit','quizzes.edit','admin.dashboard','users.index','users.edit','users.show'];
foreach ($names as $n) {
    $r = $routes->getByName($n);
    echo $n.' => '.implode(',', $r->parameterNames()).PHP_EOL;
}
