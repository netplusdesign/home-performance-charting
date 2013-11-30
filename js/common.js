/**
 * @author Larry Burks
 * common.js
 */
	// this first function fixes a orientation bug, via...
	// http://webdesignerwall.com/tutorials/iphone-safari-viewport-scaling-bug
	(function(doc) 
	{
		var addEvent = 'addEventListener',
		type = 'gesturestart',
		qsa = 'querySelectorAll',
		scales = [1, 1],
		meta = qsa in doc ? doc[qsa]('meta[name=viewport]') : [];
		function fix() 
		{
		    meta.content = 'width=device-width,minimum-scale=' + scales[0] + ',maximum-scale=' + scales[1];
		    doc.removeEventListener(type, fix, true);
		}
	    if ((meta = meta[meta.length - 1]) && addEvent in doc) 
	    {
	        fix();
	        scales = [.25, 1.6];
	        doc[addEvent](type, fix, true);
	    }
	}(document));
	
	function makeTags(tag, n) 
	{
		var str = '<' + tag + '></' + tag + '>', fstr = '';
		for (i=0; i<n; i++) { fstr = fstr + str; }
		return fstr;
	}
	
	function getStartDay( d )
	{
		return new Date(d.getFullYear(), d.getMonth(), 1).getDay(); 
	}	
	Date.prototype.isBefore = function( d )
	{
		return d.getTime() > this.getTime() ? true : false; 
	}
	Date.prototype.isAfter = function( d )
	{
		return d.getTime() < this.getTime() ? true : false; 
	}