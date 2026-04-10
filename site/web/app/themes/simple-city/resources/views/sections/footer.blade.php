<footer class="content-info">

  <div class="footer-top">
    <div class="footer-container">
      <div class="footer-brand">
        @if(function_exists('the_custom_logo'))
          @php(the_custom_logo())
        @endif
      </div>
      <p class="footer-tagline">Η μεγαλύτερη επιλογή, η καλύτερη τιμή.</p>
    </div>
  </div>

  <div class="footer-widgets-area">
    <div class="footer-container">
      @php(dynamic_sidebar('sidebar-footer'))
    </div>
  </div>

  <div class="footer-bottom">
    <div class="footer-container">
      <p class="footer-copy">&copy; {{ date('Y') }} eboy.gr &mdash; Με επιφύλαξη παντός δικαιώματος.</p>
    </div>
  </div>

</footer>
