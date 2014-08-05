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
		$(document).ready(function(){
			$.ajax({
				url: "api.php?request=getAllCrons",
				success: function(data){
					var list = [];
					for(i in data.data)
					{
						var apiItem = data.data[i];
						var $item = $('<a href="#" class="list-group-item"/>');
						
						var text = apiItem.name;
						if(apiItem.isRunning == 1)
							text += '<span class="label label-success" style="float:right;">Running.. ( '+ apiItem.runningTime +'s )</span>';
						else
							text += '<span class="label label-default" style="float:right;">'+ apiItem['lastRunTime'] +' ( '+ apiItem['lastDuration'] +'s )</span>';

						$item.html(text);
						$(".cronList").append($item);
					}
				}
			});
			getLogsForCron(1);
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
	function getLogsForCron(id)
	{
		$.ajax({
			url: "api.php?request=getLogsForCron&cronID=" + id,
			success: function(data){
				var list = [];
				for(i in data.data.logs)
				{
					var apiItem = data.data.logs[i];
					var $item = $('<a href="#" class="list-group-item"/>');
					if(apiItem.type == "stderr")
						$item.addClass("list-group-item-danger");

					var text = apiItem.output;
					
					$item.html(text.htmlEscape().replace("\n","<br/>"));
					$(".cronLogs").append($item);
				}
				if(data.data.isRunning == "1")
				{
					setTimeout(function(){
						getLogsForCron(id);
					},3000);			
				}
			}
		});
	}
	</script>

	</head>
	<body>
		<div style="margin:20px;">
			<div class="list-group cronList" style="float:left;width:300px;">
			
			</div>
			<div class="list-group cronLogs" style="float:left;max-width: 800px;margin-left: 20px;height:90%;overflow-y:auto">
				
			</div>
		</div>
	</body>
</html>
