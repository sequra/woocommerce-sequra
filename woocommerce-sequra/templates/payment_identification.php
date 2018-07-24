<?php
if($this->identity_form){
	echo $this->identity_form; ?>
<script type="text/javascript">
    function tryToOpenPumbaa(){
        try{
            window.SequraFormInstance.setCloseCallback(function (){
                document.location.href = '<?php echo wc_get_checkout_url(); ?>';
            });
            window.SequraFormInstance.show();
            jQuery('.sq-identification-iframe').appendTo('body');
        }catch(e){
            setTimeout(tryToOpenPumbaa,500);
        }
    }

    jQuery(function(){
        tryToOpenPumbaa();
    });
</script>
<?php } else { ?>
	<script type="text/javascript">
		alert("Lo sentimos, ha habido un error.\n Contacte con el comercio, por favor.");
		document.location.href = history.back(1);
	</script>
<?php } ?>
