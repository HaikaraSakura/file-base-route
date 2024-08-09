# file-base-route

```PHP
// Routerにルーティングファイルの設置場所を渡してインスタンス化
$router = new Router('./routes');

// サブディレクトリ運用の場合はプレフィックスとなるパスを渡す
$router->setBasePath('/sub');

$router->setActionInvoker(
    fn (Closure $action) => $container->call($action)
);

$response = $router->handle($request);
```
