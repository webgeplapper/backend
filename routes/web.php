<?php

$app->group(['prefix' => 'api'], function () use ($app) {

    $app->get('/episodes', [
      'uses' => 'EpisodesController@index'
    ]);

    $app->get('/episode/{id}', [
      'uses' => 'EpisodesController@view'
    ]);

});

$app->get('/', function () use ($app) {
    return $app->version();
});
