{{--
  Template Name: Custom Monk Template 
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())

  @include('partials.monk-vue3_carousel')
  @include('partials.main-cats')
  @include('partials.content-menu')
  @include('partials.archive-simple')

  @endwhile
@endsection

