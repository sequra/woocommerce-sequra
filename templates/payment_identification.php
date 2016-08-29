<?php echo $this->identity_form; ?>
<script type="text/javascript">
	window.SequraFormInstance.setCloseCallback(function (){
		document.location.href = history.back(1);
	});
	window.SequraFormInstance.show();
</script>