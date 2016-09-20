/** chat **/		
var chatLoaded = false;
var pollChat = null;
var lastChatId = 0;

$(document).ready(function(){
			
	$("#statstable").tablesorter();
	$("td.user a").cluetip(); 
	
	$("#quicksearch, #askquestion, #submitBug, #chat").jqm();
	
	for (c = 0; c < 3; c++) mSheets[c] = new MenuSheet();  
	// Menu 0 - questions
	mSheets[0].addLink("Последние", "kontra.php?action=browse&sort=date&type=answe&time=all&count=50");
	mSheets[0].addLink("Рейтинговые", "kontra.php?action=browse&sort=rate&type=answe&time=all&count=50");

	// Menu 1 - answers
	mSheets[1].addLink("Последние", "kontra.php?action=browse&sort=date&type=quest&time=all&count=50");
	mSheets[1].addLink("Рейтинговые", "kontra.php?action=browse&sort=rate&type=quest&time=all&count=50");
	mSheets[1].addLink("Не отвеченные", "kontra.php?action=browse&sort=notan&type=quest&time=all&count=50");
	mSheets[1].addLink("Посещаемые", "kontra.php?action=browse&sort=shows&type=quest&time=all&count=50");
	
	mSheets[2].addLink("Поглядеть остальные баги", "kontra.php?action=viewbugs");

	for (c = 0; c < 3; c++) mSheets[c].create();
	mReady = true;
	
	$('.tq_rate').each(function(){
		var val = $(this).find('b').text();
		if (val >= 10) $(this).css('background', '#FF0000');
		else if (val >= 5) $(this).css('background', '#4AB847');
		else if (val == 0) $(this).css('background', '#C0C0C0');
	});

	$('#chat').bgiframe();
	$('#chat-close').click(function(){ $('#chat').jqmHide(); });
	$('#chatTrigger')
		.css('opacity', 0.5)
		.mouseover(function(){$(this).css('opacity', 1);})
		.mouseout(function(){$(this).css('opacity', 0.5);})
		.click(function(){ 
			if (!chatLoaded) {
				refreshChat();
				chatLoaded = true;
			}
			$('#chat').jqmShow(); 			
			$('#chatTrigger').css('backgroundColor', '#cceecc').css('opacity', '0.5');
		});
		
	pollChat = setInterval(refreshChat, 10000);	// updating chat every 10 seconds
		
	$('#chat-say').click(submitChatMessage);	
	$('#chat-msg').keydown(function(e){	if (e.keyCode == 13) submitChatMessage(); })
	
	$(document)
		.ajaxError(function() { $('#ajax-loading').hide(); })
		.ajaxStop(function() { $('#ajax-loading').hide(); })
		.ajaxStart(function() { $('#ajax-loading').show(); });
		
		
	/* votings */
	
	$('span.question-votes, span.answer-votes').each(function(){
		if ($(this).attr('voted') == 'false') {
			$(this).hide()
				.parent().find('select').show().change(doVote);
		} else {
			$(this).parent().parent().find('div.user').show();
		}
	});		
});

function doVote(evt) {
	var selNode = this;	
	var value = this.value;	
	var qid = this.name;
	var methodName = (qid.match(/^a/i) ? 'votea' : 'voteq');	
	
	$(this).hide().parent().append('<img src="images/ajax-loader.gif" />');
	
	$.post('service.php?action=' + methodName, {id: qid, rate: value}, 
		function(data){
			$(selNode).parent()
				.find('span').show().text($.trim(data)).end()
				.find('img').remove().end()
				.parent().find('div.user').show();;
				
		});
}

function refreshChat() {
	$.get('service.php?action=chat&id='+lastChatId, {}, updateMessages);	
}

function updateMessages(data) {
	// opera 9.2 fix
		
	if (data == '') return;
	var mm = $('#chat .messages');
	mm.html(data + mm.html());
	
	var lastMsg = $('div', mm).eq(0);
	var prevId = lastChatId;
	lastChatId = lastMsg.attr('cid');
	var newText = '#'+ lastChatId + ' ' + $('span.user', lastMsg).text() + ' в ' + $('span.date', lastMsg).text();
	$('#chatTrigger').text(newText);
	
	if (prevId != lastChatId && prevId > 0 && $('#chat').css('display') == 'none') {
		$('#chatTrigger').css('backgroundColor', '#ffcccc').css('opacity', '1');
	}
}

function submitChatMessage() {
	var val = $('#chat-msg').attr('disabled', 'true').val();
	if (val != '') {		
		$.post('service.php?action=chatsay&id='+lastChatId, {message: val}, function(data){
			updateMessages(data);
			$('#chat-msg').removeAttr('disabled').val('');
		});		
	}
}