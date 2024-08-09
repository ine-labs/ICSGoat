$(document).ready(function() {

	$('#customgroups-admin input').change(function() {
		var value = 'false';
		if (this.checked) {
			value = 'true';
		}
		OC.AppConfig.setValue('customgroups', $(this).attr('name'), value);
	});

	$('.section .icon-info').tipsy({gravity: 'w'});
});
