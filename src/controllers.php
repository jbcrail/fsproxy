<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\Adapter\Local as LocalAdapter;

$adapters = array(
  'local' => new LocalAdapter('.'),

  'remote' => new FtpAdapter(array(
    'host' => 'test.talia.net',
    'username' => 'anonymous',
    'password' => 'ignore@me.com',
    'port' => 21,
    'root' => '.',
    'passive' => true,
    'ssl' => false,
    'timeout' => 30,
  )),
);

$app->get('/', function () use ($app) {
  return $app->redirect('/fs');
});

$app->get('/fs', function (Request $request) use ($app, $adapters) {
  $accept = AcceptHeader::fromString($request->headers->get('Accept'));
  if ($accept->has('text/html')) {
    return $app['twig']->render('fs.html', array('adapters' => $adapters));
  }
  else if ($accept->has('application/json')) {
    return $app->json(array_keys($adapters));
  }
  else {
    $app->abort(404, "Unsupported accept header");
  }
});

$app->get('/fs/{site}', function (Request $request, $site) use ($app, $adapters) {
  if (!isset($adapters[$site])) {
    $app->abort(404, "Site $site does not exist.");
  }

  $fs = new Filesystem($adapters[$site]);

  $accept = AcceptHeader::fromString($request->headers->get('Accept'));
  if ($accept->has('text/html')) {
    return $app['twig']->render('listing.html', array('site' => $site, 'files' => $fs->listContents()));
  }
  else if ($accept->has('application/json')) {
    return $app->json($fs->listContents());
  }
  else {
    $app->abort(404, "Unsupported accept header");
  }
});

$app->get('/fs/{site}/{url}', function (Request $request, $site, $url) use ($app, $adapters) {
  if (!isset($adapters[$site])) {
    $app->abort(404, "Site $site does not exist.");
  }

  $fs = new Filesystem($adapters[$site]);
  return $fs->read($url);
});

$app->error(function (\Exception $e, $code) use ($app) {
  if ($app['debug']) {
    return;
  }

  // 404.html, or 40x.html, or 4xx.html, or error.html
  $templates = array(
    'errors/'.$code.'.html',
    'errors/'.substr($code, 0, 2).'x.html',
    'errors/'.substr($code, 0, 1).'xx.html',
    'errors/default.html',
  );

  return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
