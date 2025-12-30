    function tbigrChangeContainer(){
        var tbigr_label_container = document.getElementsByClassName("tbigr-label-container")[0];
        if (tbigr_label_container.style.visibility == 'visible'){
            tbigr_label_container.style.visibility = 'hidden';
            tbigr_label_container.style.opacity = 0;
            tbigr_label_container.style.transition = 'visibility 0s, opacity 0.5s ease';                
        }else{
            tbigr_label_container.style.visibility = 'visible';
            tbigr_label_container.style.opacity = 1;            
        }
    }