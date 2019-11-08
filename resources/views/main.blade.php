@extends('layouts.app')

@section('content')
<media-clerk ref='mediaClerk' api-token='{{ $api_token }}'></media-clerk>
@endsection