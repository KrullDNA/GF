
//----------------------------------------------------------
//------ JAVASCRIPT HOOK FUNCTIONS FOR KDNA Forms -------
//----------------------------------------------------------

if ( ! kform ) {
	document.addEventListener( 'kform_main_scripts_loaded', function() { kform.scriptsLoaded = true; } );
	document.addEventListener( 'kform/theme/scripts_loaded', function() { kform.themeScriptsLoaded = true; } );
	window.addEventListener( 'DOMContentLoaded', function() { kform.domLoaded = true; } );

	var kform = {
		domLoaded: false,
		scriptsLoaded: false,
		themeScriptsLoaded: false,
		isFormEditor: () => typeof InitializeEditor === 'function',

		/**
		 * @deprecated 2.9 the use of initializeOnLoaded in the form editor context is deprecated.
		 * @remove-in 4.0 this function will not check for kform.isFormEditor().
		 */
		callIfLoaded: function ( fn ) {
			if ( kform.domLoaded && kform.scriptsLoaded && ( kform.themeScriptsLoaded || kform.isFormEditor() ) ) {
				if ( kform.isFormEditor() ) {
					console.warn( 'The use of kform.initializeOnLoaded() is deprecated in the form editor context and will be removed in KDNA Forms 3.1.' );
				}
				fn();
				return true;
			}
			return false;
		},

		/**
		 * Call a function when all scripts are loaded
		 *
		 * @param function fn the callback function to call when all scripts are loaded
		 *
		 * @returns void
		 */
		initializeOnLoaded: function( fn ) {
			if ( ! kform.callIfLoaded( fn ) ) {
				document.addEventListener( 'kform_main_scripts_loaded', () => { kform.scriptsLoaded = true; kform.callIfLoaded( fn ); } );
				document.addEventListener( 'kform/theme/scripts_loaded', () => { kform.themeScriptsLoaded = true; kform.callIfLoaded( fn ); } );
				window.addEventListener( 'DOMContentLoaded', () => { kform.domLoaded = true; kform.callIfLoaded( fn ); } );
			}
		},

		hooks: { action: {}, filter: {} },
		addAction: function( action, callable, priority, tag ) {
			kform.addHook( 'action', action, callable, priority, tag );
		},
		addFilter: function( action, callable, priority, tag ) {
			kform.addHook( 'filter', action, callable, priority, tag );
		},
		doAction: function( action ) {
			kform.doHook( 'action', action, arguments );
		},
		applyFilters: function( action ) {
			return kform.doHook( 'filter', action, arguments );
		},
		removeAction: function( action, tag ) {
			kform.removeHook( 'action', action, tag );
		},
		removeFilter: function( action, priority, tag ) {
			kform.removeHook( 'filter', action, priority, tag );
		},
		addHook: function( hookType, action, callable, priority, tag ) {
			if ( undefined == kform.hooks[hookType][action] ) {
				kform.hooks[hookType][action] = [];
			}
			var hooks = kform.hooks[hookType][action];
			if ( undefined == tag ) {
				tag = action + '_' + hooks.length;
			}
			if( priority == undefined ){
				priority = 10;
			}

			kform.hooks[hookType][action].push( { tag:tag, callable:callable, priority:priority } );
		},
		doHook: function( hookType, action, args ) {

			// splice args from object into array and remove first index which is the hook name
			args = Array.prototype.slice.call(args, 1);

			if ( undefined != kform.hooks[hookType][action] ) {
				var hooks = kform.hooks[hookType][action], hook;
				//sort by priority
				hooks.sort(function(a,b){return a["priority"]-b["priority"]});

				hooks.forEach( function( hookItem ) {
					hook = hookItem.callable;

					if(typeof hook != 'function')
						hook = window[hook];
					if ( 'action' == hookType ) {
						hook.apply(null, args);
					} else {
						args[0] = hook.apply(null, args);
					}
				} );
			}
			if ( 'filter'==hookType ) {
				return args[0];
			}
		},
		removeHook: function( hookType, action, priority, tag ) {
			if ( undefined != kform.hooks[hookType][action] ) {
				var hooks = kform.hooks[hookType][action];
				hooks = hooks.filter( function(hook, index, arr) {
					var removeHook = (undefined==tag||tag==hook.tag) && (undefined==priority||priority==hook.priority);
					return !removeHook;
				} );
				kform.hooks[hookType][action] = hooks;
			}
		}
	};
}
