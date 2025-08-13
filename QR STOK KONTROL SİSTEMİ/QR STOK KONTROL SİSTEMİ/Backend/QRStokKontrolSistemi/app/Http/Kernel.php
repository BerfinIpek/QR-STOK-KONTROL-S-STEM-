protected $routeMiddleware = [
    // ... diğer middleware'ler
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'user' => \App\Http\Middleware\UserMiddleware::class,
];