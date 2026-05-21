{{--
  Template Name: Custom Template (Hide Filters)
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())

  @include('partials.hero-embla')
  @include('partials.content-menu')
  @include('partials.archive-simple-hide-filters')

  @endwhile
@endsection
