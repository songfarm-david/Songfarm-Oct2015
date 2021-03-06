var slideVars = {
		screenSize:'',
		width:0,
		mobileSize:480,
		autoPlay:false,
		currentPanel:1,
		totalPanels:0,
		timePassed:0,
		timeToChange:60,
		duration:1250,
		inTransition:false,
		panelContent:Array
};

$(document).ready(function(){
	slideGatherData();
});

/**
* Gather slide data
*/
function slideGatherData(){
	$('.slide-data .slide-panel').each(function(index){
		slideVars.totalPanels = index + 1;
		var panel_image_l = $(this).attr('data-image') +'.jpg';
		var panel_image_s = $(this).attr('data-image') +'_s.jpg';
		var panel_caption = $(this).html();

		var slideWidth = $('.slide-container').width() +18; /* to account for a few extra pixels */
		if( slideWidth > 700 ){
			var content = '<img src="images/arrows_and_lines/hr_dotted_white.png">'
		} else {
			var content = '<img src="images/arrows_and_lines/hr_dotted_white_short.png">';
		}

		/* insert an img tag after h3 tag */
		var position = panel_caption.indexOf('</h3>');
		var panel_caption = [panel_caption.slice(0,position+5), content, panel_caption.slice(position+5)].join('');

		slideVars.panelContent[index] = '<div class="slide-panel" data-image-s="'+panel_image_s+'" style="background-image:url('+panel_image_l+'); height: 470px; "><div class="panel-caption">'+panel_caption+'</div></div>';
	});

	setInterval(slideAdvance,150);

}

function slideAdvance(){
	var slideWidth = $('.slide-container').width() +18; /* to account for a few extra pixels */
	var currentSize = slideVars.screenSize;
	/* determine whether mobileSize */
	if ( slideWidth > slideVars.mobileSize ){
		var newSize = 'large';
	} else {
		var newSize = 'small';
	}
	slideVars.screenSize = newSize;
	if(	currentSize != newSize ){
		if( slideVars.screenSize == 'large'){
			slideMultiPanel();
		} else {
			slideSinglePanel();
		}
	}
	if ( slideVars.timePassed == slideVars.timeToChange ){
		slideVars.timePassed = 0;
		if ( slideVars.autoPlay == true ){
			/* nav triggers */
			if( slideVars.currentPanel == slideVars.totalPanels ){
				$('.slide-nav div:nth-child(1)').trigger('click');
			} else {
				$('.slide-nav div:nth-child('+(slideVars.currentPanel+1)+')').trigger('click');
			}
		}
	} else {
		slideVars.timePassed += 1;
	}

	horizontalRuleLength(slideWidth);

};

function slideMultiPanel(){
	slideVars.timePassed = 0;
	slideVars.autoPlay = true;

	var newHTML = '<div class="slide-stage-large"><div class="slide-container-1"></div><div class="slide-nav"></div><div class="btn prev"></div><div class="btn next"></div></div>';
	$('.slide-container').html('').append(newHTML);

	for( i = 0; i < slideVars.totalPanels; i++ ){
		$('.slide-nav').append('<div></div>');
	}

	$('.slide-container').hover(function(){
		slideVars.autoPlay = false;
	},function(){
		slideVars.autoPlay = false;
		slideVars.timePassed = Math.floor(slideVars.timeToChange / 2);
	});

	$('.slide-container .btn').on('click', function(){
		if( !slideVars.inTransition ){

			if ( $(this).hasClass('prev') ){
				slideVars.currentPanel -= 1;
				if ( slideVars.currentPanel < 1 ){
					slideVars.currentPanel = slideVars.totalPanels;
				}
			} else {
				slideVars.currentPanel += 1;
				if( slideVars.currentPanel > slideVars.totalPanels){
					slideVars.currentPanel = 1;
				}
			}

			$('.slide-nav div:nth-child('+slideVars.currentPanel+')').trigger('click');
		}
	});

	$('.slide-nav div').on('click', function(){
		if( !slideVars.inTransition ){

			slideVars.inTransition = true;

			var navClicked = $(this).index();
			slideVars.currentPanel = navClicked + 1;

			$('.slide-nav div').removeClass('selected');
			$(this).addClass('selected');

			$('.slide-stage-large').append('<div class="slide-container-2" style="opacity:0;"></div>');
			$('.slide-container-2').html(slideVars.panelContent[navClicked]).animate({opacity:1},slideVars.duration,function(){
				$('.slide-container-1').remove();
				$(this).addClass('slide-container-1').removeClass('slide-container-2');
				slideVars.inTransition = false;
			});
		}
	});

	$('.slide-nav div:first').trigger('click');

}

function slideSinglePanel(){
	$('.slide-container').html('').append('<div class="slide-stage-small">'+slideVars.panelContent[0]+'</div>'); //index number corresponds to panel...
	var panel_image_s = $('.slide-container .slide-stage-small .slide-panel').attr('data-image-s');
	$('.slide-container .slide-stage-small .slide-panel').css('background-image','url('+panel_image_s+')');
}

function horizontalRuleLength( slideWidth ){
	var target = $('.slide-container .panel-caption img');
	if( slideWidth < 700 ){
		target.attr('src','images/arrows_and_lines/hr_dotted_white_short.png');
	} else {
		target.attr('src','images/arrows_and_lines/hr_dotted_white.png');
	}
}

/*
//debugger
var debugTimer = setInterval(setDebugger,100);
function setDebugger(){
	$('.var1').html('screenSize = '+slideVars.screenSize);
	$('.var2').html('width = '+slideVars.width);
	$('.var3').html('mobileSize = '+slideVars.mobileSize);
	$('.var4').html('autoPlay = '+slideVars.autoPlay);
	$('.var5').html('currentPanel = '+slideVars.currentPanel);
	$('.var6').html('totalPanels = '+slideVars.totalPanels);
	$('.var7').html('timePassed = '+slideVars.timePassed);
	$('.var8').html('timeToChange = '+slideVars.timeToChange);
	$('.var9').html('inTransition = '+slideVars.inTransition);
};
*/
