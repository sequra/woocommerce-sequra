<?php
if($this->identity_form){
	echo $this->identity_form; ?>
<script type="text/javascript">
	window.SequraFormInstance.setCloseCallback(function (){
		document.location.href = history.back(1);
	});
	window.SequraFormInstance.show();
	jQuery('.sq-identification-iframe').appendTo('body');
</script>
<?php } else { ?>
	<script type="text/javascript">
		alert("Lo sentimos, ha habido un error.\n Contacte con el comercio, por favor.");
		document.location.href = history.back(1);
	</script>
<?php } ?>
