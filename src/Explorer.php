<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute;

use FilesystemIterator;
use Psr\Http\Message\ServerRequestInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionFunction;
use Haikara\FileBaseRoute\Attribute\RouteName;
use Haikara\FileBaseRoute\Exception\ActionException;

class Explorer {
    protected string $basePath;
    protected string $baseDirectory;

    public function __construct(string $basePath, string $baseDirectory) {
        $this->basePath = '/' . trim($basePath, '/');
        $this->baseDirectory = '/' . trim(realpath($baseDirectory), '/');
    }

    /**
     * @param ServerRequestInterface $request
     * @returns array{refFanc:ReflectionFunction|null, request:ServerRequestInterface}
     * @throws ActionException
     */
    public function explore(ServerRequestInterface $request): array {
        $requestPath = '/' . substr($request->getUri()->getPath(), strlen($this->basePath));

        $routeIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDirectory, FilesystemIterator::SKIP_DOTS)
        );

        $refAction = null;
        $namedRoutePatterns = [];

        foreach ($routeIterator as $fileinfo) {
            if ($fileinfo->isDir()) {
                continue;
            }

            $filename = $fileinfo->getBaseName();

            if (!str_ends_with($filename, '.php')) {
                continue;
            }

            $pattern = substr($fileinfo->getRealPath(), strlen($this->baseDirectory));
            $directory = '/' . trim(substr($fileinfo->getPath(), strlen($this->baseDirectory)), '/');

            if (str_starts_with($filename, 'index.')) {
                // ディレクトリのパスをルーティングパターンとする
                $pattern = $directory;
            } else{
                // 拡張子を取り除いた文字列をルーティングパターンとする
                $offset = strpos($filename, '.');
                $pattern = rtrim($directory, '/') . '/' . substr($filename, 0, $offset);
            }
            $action = require $fileinfo->getRealPath();

            if (!is_callable($action)) {
                throw new ActionException('ルートファイルはcallableな値を返す必要があります。');
            }

            $refFunc = new ReflectionFunction($action(...));

            // 名前付きルートのみルートパターンを記憶
            $routeNameAttr = $refFunc->getAttributes(RouteName::class)[0] ?? null;

            if ($routeNameAttr instanceof ReflectionAttribute) {
                $routeName = $routeNameAttr->newInstance()->name;
                $namedRoutePatterns[$routeName] = $pattern;
            }

            if (static::isPlaceholder($pattern)) {
                $args = [];

                $requestPathSegments = explode('/', trim($requestPath, '/'));
                $patternSegments = explode('/', trim($pattern, '/'));

                // セグメントの数が合わなければ不一致
                if (count($requestPathSegments) !== count($patternSegments)) {
                    continue;
                }

                foreach ($patternSegments as $index => $patternSegment) {
                    // プレースホルダーなら無条件で合致扱い
                    if (static::isPlaceholder($patternSegment)) {
                        $argKey = rtrim(ltrim($patternSegment, '{'), '}');
                        $args[$argKey] = $requestPathSegments[$index];
                        continue;
                    }

                    if ($patternSegment !== $requestPathSegments[$index]) {
                        break;
                    }
                }

                $request = $request->withAttribute(Router::class, [
                    'args' => $args,
                    'named_routes' => $namedRoutePatterns
                ]);

                $refAction = $refFunc;
                break;
            }

            if ($pattern === $requestPath) {
                $refAction = $refFunc;
                break;
            }
        }

        return ['refFunc' => $refAction, 'request' => $request];
    }

    protected static function isPlaceholder(string $pattern): bool {
        return str_starts_with($pattern, '{') && str_ends_with($pattern, '}');
    }
}
