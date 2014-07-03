<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use League\Flysystem\Filesystem;

$app->get('/{site}/{url}', function (Request $request, $site, $url) use ($app) {
  if (!isset($app['proxy.sites'][$site])) {
    $app->abort(404, "Site $site does not exist.");
  }

  $fs = new Filesystem($app['proxy.sites'][$site]);

  /*
   * FIXME: Workaround for Flysystem's inability to get a directory's
   * metadata without retrieving the listing first. I get the listing
   * twice, and this needs to be simplified.
   */
  $dir = dirname($url);
  $fs->listContents($dir === '.' ? '' : $dir);

  $info = $fs->getMetadata($url);
  if ($info['type'] === 'file') {
    return new Response($fs->read($url), 200, array('Content-Type' => $fs->getMimetype($url)));
  }

  $files = $fs->listContents($url);
  foreach ($files as $k => $v) {
    if (isset($v['timestamp'])) {
      $files[$k]['timestamp'] = date("d-M-Y H:i", $v['timestamp']);
    }
  }

  $accept = AcceptHeader::fromString($request->headers->get('Accept'));
  if ($accept->has('text/html')) {
    return $app['twig']->render('listing.html', array('title' => $app['proxy.title'], 'site' => $site, 'path' => $url, 'files' => $files));
  }
  else if ($accept->has('application/json')) {
    return $app->json($files);
  }
  else {
    $app->abort(404, "Unsupported accept header");
  }
})->assert('url', '.*');

$app->get('/{site}', function (Request $request, $site) use ($app) {
  if (!isset($app['proxy.sites'][$site])) {
    $app->abort(404, "Site $site does not exist.");
  }

  $fs = new Filesystem($app['proxy.sites'][$site]);
  $files = $fs->listContents();
  foreach ($files as $k => $v) {
    if (isset($v['timestamp'])) {
      $files[$k]['timestamp'] = date("d-M-Y H:i", $v['timestamp']);
    }
  }

  $accept = AcceptHeader::fromString($request->headers->get('Accept'));
  if ($accept->has('text/html')) {
    return $app['twig']->render('listing.html', array('title' => $app['proxy.title'], 'site' => $site, 'path' => '', 'files' => $files));
  }
  else if ($accept->has('application/json')) {
    return $app->json($files);
  }
  else {
    $app->abort(404, "Unsupported accept header");
  }
});

$app->get('/', function (Request $request) use ($app) {
  $accept = AcceptHeader::fromString($request->headers->get('Accept'));
  if ($accept->has('text/html')) {
    return $app['twig']->render('index.html', array('title' => $app['proxy.title'], 'sites' => $app['proxy.sites']));
  }
  else if ($accept->has('application/json')) {
    return $app->json(array_keys($app['proxy.sites']));
  }
  else {
    $app->abort(404, "Unsupported accept header");
  }
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
