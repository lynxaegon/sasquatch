<html>
	<head>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

	<!-- Optional theme -->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">

	<!-- Latest compiled and minified JavaScript -->
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
	<script>
		var lastLogRowID = 0;
		function onHashChange()
		{
			var hash = location.hash

			if(matches = hash.match(/\#cronID-(\d+)$/i))
			{
				var cronID = matches[1];
				// should show cron run list

				getCronRunList(cronID);
				$(".cronRunList").show();
				$(".cronLogsZone").hide();
			}
			else if(matches = hash.match(/\#cronID-(\d+)\/runID-(.+)/i))
			{
				var cronID = matches[1];
				var runID = matches[2];
				// should show cron run log

				$(".cronRunList").hide();
				$(".cronLogsZone").show();
				$(".cronLogs").empty();
				$(".cronLogTimes").empty();
				getLogsForCron(cronID, runID);
			}
		}
		$(window).on('hashchange',function(){ 
		    onHashChange();
		});
		
		$(document).ready(function(){
			$(".cronLogsZone").css({
				"width" : $(window).width() - $(".cronList").width() - 20 * 2 // 20 is the margin
			});
			onHashChange();
			$.ajax({
				url: "api.php?request=getAllCrons",
				success: function(data){
					var list = [];
					for(i in data.data)
					{
						var apiItem = data.data[i];
						var $item = $('<a href="#cronID-'+ apiItem.ID +'" class="list-group-item"/>');
						
						var text = apiItem.name;
						if(apiItem.isRunning == 1)
							text += '<span class="label label-success" style="float:right;">Running.. '+ apiItem.runningTime +'</span>';
						else
							text += '<span class="label label-default" style="float:right;">'+ apiItem['lastRunTime'] +'</span>';

						$item.html(text);
						$(".cronList").append($item);
					}
				}
			});
		});
	(function(undefined){
	    var charsToReplace = {
	        '&': '&amp;',
	        '<': '&lt;',
	        '>': '&gt;'
	    };

	    var replaceReg = new RegExp("[" + Object.keys(charsToReplace).join("") + "]", "g");
	    var replaceFn = function(tag){ return charsToReplace[tag] || tag; };

	    var replaceRegF = function(replaceMap) {
	        return (new RegExp("[" + Object.keys(charsToReplace).concat(Object.keys(replaceMap)).join("") + "]", "gi"));
	    };
	    var replaceFnF = function(replaceMap) {
	        return function(tag){ return replaceMap[tag] || charsToReplace[tag] || tag; };
	    };

	    String.prototype.htmlEscape = function(replaceMap) {
	        if (replaceMap === undefined) return this.replace(replaceReg, replaceFn);
	        return this.replace(replaceRegF(replaceMap), replaceFnF(replaceMap));
	    };
	})();
	function getCronRunList(cronID)
	{
		$(".cronRunList").empty();
		$(".cronLogTimes").empty();
		$.ajax({
				url: "api.php?request=getCronRunList&cronID=" + cronID,
				success: function(data){
					var list = [];
					for(i in data.data)
					{
						var apiItem = data.data[i];
						var $item = $('<a href="#cronID-'+ apiItem.cronID +'/runID-'+ apiItem.runID +'" class="list-group-item"/>');
						var text = apiItem.startDateTime;
						if(apiItem.isRunning == 1)
							text += '<span class="label label-success" style="float:right;">Running.. '+ apiItem.runningTime +'</span>';
						else
							text += '<span class="label label-default" style="float:right;">'+ apiItem['runningTime'] +'</span>';

						$item.html(text);
						$(".cronRunList").append($item);
					}
				}
			});
	}
	function getLogsForCron(cronID, runID)
	{
		// $(".cronLogs").empty();
		runID = runID || "";
		$.ajax({
			url: "api.php?request=getLogsForCron&cronID=" + cronID + "&runID="+ runID + "&lastRowID="+lastLogRowID,
			success: function(data){
				var list = [];
				var $tmpCronLogs = $().add("<span/>");
				var $tmpCronLogTimes = $().add("<span/>");

				for(i in data.data.logs)
				{
					var apiItem = data.data.logs[i];
					var $item = $('<div/>');
					var $timeStamp = $("<div/>");

					$timeStamp.html(apiItem.logTime);
					$item.html(apiItem.output.htmlEscape())

					if(apiItem.type == "stderr")
					{
						$timeStamp.css({
							"background-color": "#f2dede"
						});
						$item.css({
							"background-color": "#f2dede"
						});
					}
					
					$item.html($item.html());
					$tmpCronLogs.append($item);
					$tmpCronLogTimes.append($timeStamp);
				}
				$(".cronLogs").append($tmpCronLogs);
				$(".cronLogTimes").append($tmpCronLogTimes);
				if( $('.cronLogsZone')[0].scrollTop + $('.cronLogsZone').height() + 200 >= $('.cronLogsZone')[0].scrollHeight )
					$(".cronLogsZone").scrollTop($('.cronLogsZone')[0].scrollHeight);
				lastLogRowID += data.data.lastRowID;
				if(data.data.isRunning == "1")
				{
					setTimeout(function(){
						getLogsForCron(cronID);
					},500);			
				}
				else
				{
					if(data.data.logs.length > 0)
					{
						setTimeout(function(){
							getLogsForCron(cronID);
						},500);		
					}
					else
					{
						lastLogRowID = 0;
					}
				}
			}
		});
	}
	</script>

	</head>
	<body>
		<div style="margin:20px;position:relative;height:94%">
			<div class="list-group cronList" style="float:left;width:300px;">
			
			</div>
			<div class="list-group cronRunList" style="display:none;float:left;width:300px;">
			
			</div>
			<pre class="cronLogsZone" style="display:none;position:absolute;left:300px;margin-left: 0px;height:100%;overflow-y:auto;width:100%"><div class="cronLogTimes" style="float:left;width: 105px;"></div><div class="cronLogs" style="float:left;position:absolute;left:105px;"></div></pre>
		</div>
	</body>
</html>
