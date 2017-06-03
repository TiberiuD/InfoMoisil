$(document).ready(function(){
	$('#loginModal').modal();
	$('#modal1').modal();	
	
	$("#userMenuButton").dropdown({
        inDuration: 300,
        outDuration: 225,
        hover: true, // Activate on hover
        belowOrigin: true, // Displays dropdown below the button
    });
    
    $('.button-collapse').sideNav();
	
	// Toggle search
	$('a#toggle-search').click(function(){
		var search = $('nav#search-bar');

		search.is(":visible") ? search.slideUp() : search.slideDown(function(){
			search.find('input').focus();
		});

		return false;
	});
});