<?php namespace aruszala\Laraberg\Http\Controllers;

use Illuminate\Http\Request;

class MediaController extends ApplicationController
{
    public function store(Request $request)
    {

        return $this->ok(\json_encode([]), 201);
    }
}
