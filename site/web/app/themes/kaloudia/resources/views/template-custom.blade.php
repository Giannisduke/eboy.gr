{{--
  Template Name: Custom Template
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())

  @include('partials.hero-vue3_carousel')
  @include('partials.main-cats')
  @include('partials.content-menu')
  @include('partials.archive-simple')

  @endwhile
@endsection

