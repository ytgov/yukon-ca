jQuery("input[value='Soumettre un commentaire (this translation)']").click(function() {
    if (jQuery(".path-form  .form-checkbox").prop("checked") == false){ 
        alert("Please Checked Generate automatic URL alias");
        jQuery(".path-form  .form-checkbox").focus();
        return false;
    }
    

});