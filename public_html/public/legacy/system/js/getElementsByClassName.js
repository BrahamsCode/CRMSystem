// Copyright 2007 futomi  http://www.html5.jp/
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//   http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

if( typeof html5jp == 'undefined' ) {
	html5jp = new Object();
}
if( typeof html5jp.dom == 'undefined' ) {
	html5jp.dom = new Object();
}
html5jp.dom.getElementsByClassName = new Object();

// getElementsByClassName(className) method
html5jp.dom.getElementsByClassName.method = function(classNames) {
	var o = html5jp.dom.getElementsByClassName;
	var tokens = o._split_a_string_on_spaces(classNames);
	var tn = tokens.length;
	var nodes = this.all ? this.all : this.getElementsByTagName("*");
	var n = nodes.length;
	var array = new Array();
	if( tn > 0 ) {
		if( document.evaluate ) {
			var contains = new Array();
			for(var i=0; i<tn; i++) {
				contains.push('contains(concat(" ",@class," "), " '+ tokens[i] + '")');
			}
			var xpathExpression = "/descendant::*[" + contains.join(" and ") + "]";
			var iterator = document.evaluate(xpathExpression, this, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
			var inum = iterator.snapshotLength;
			for( var i=0; i<inum; i++ ) {
				var elm = iterator.snapshotItem(i);
				if( elm != this ) {
					array.push(iterator.snapshotItem(i));
				}
			}
		} else {
			for(var i=0; i<n; i++) {
				var elm = nodes.item(i);
				if( elm.className == "" ) { continue; }
				var class_list = o._split_a_string_on_spaces(elm.className);
				var class_name = class_list.join(" ");
				var f = true;
				for(var j=0; j<tokens.length; j++) {
					var re = new RegExp('(^|\\s)' + tokens[j] + '(\\s|$)')
					if( ! re.test(class_name) ) {
						f = false;
						break;
					}
				}
				if(f == true) {
					array.push(elm);
				}
			}
		}
	}
	// add item(index) method to the array as if it behave as a NodeList interface.
	array.item = function(index) {
		if(array[index]) {
			return array[index];
		} else {
			return null;
		}
	};
	//
	return array;
};
// split a string on spaces
html5jp.dom.getElementsByClassName._split_a_string_on_spaces = function(string) {
	string = string.replace(/^[\t\s]+/, "");
	string = string.replace(/[\t\s]+$/, "");
	var tokens = string.split(/[\t\s]+/);
	return tokens;
};
// find this script file path
html5jp.dom.getElementsByClassName.find_this_script_file_path = function() {
	var scripts = document.getElementsByTagName("SCRIPT");
	var path = "";
	for( var i=0; i<scripts.length; i++ ) {
		var elm = scripts.item(i);
		if( elm.src.match(/(^|\/)getElementsByClassName\.js$/) ) {
			path = elm.src.replace("getElementsByClassName.js", "");
			break;
		}
	}
	return path;
};
// set getElementsByClassName(classNames) method to objects of HTMLElement and Document interface;
html5jp.dom.getElementsByClassName.attach_method = function() {
	var o = html5jp.dom.getElementsByClassName;
	if( document.getElementsByClassName ) {
		// do nothing
	} else if( window.HTMLElement && window.Document ) { // for Firefox 1.5-2.0, Safari 3.0
		if( window.HTMLElement.prototype.getElementsByClassName == undefined ) {
			window.HTMLElement.prototype.getElementsByClassName = o.method;
		}
		if( window.Document.prototype.getElementsByClassName == undefined ) {
			window.Document.prototype.getElementsByClassName = o.method;
		}
	} else if( document.constructor && document.createElement('a').constructor ) { // for Safari 1.3-2.0
		document.createElement('a').constructor.prototype.getElementsByClassName = o.method;
		document.constructor.prototype.getElementsByClassName = o.method;
	} else if(document.uniqueID) { // for Internet Explorer
		var path = o.find_this_script_file_path();
		document.write('<style type="text/css">* { behavior: url(' + path + 'getElementsByClassName.htc); }</style>');
		document.getElementsByClassName = o.method;
	}
};

html5jp.dom.getElementsByClassName.attach_method();
