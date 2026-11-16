@extends('errors.layout')

@section('title', 'Too Many Requests')
@section('code', '429')
@section('message', 'Too Many Requests')
@section('description', 'Rate limit exceeded. Please wait a few moments before submitting again.')
