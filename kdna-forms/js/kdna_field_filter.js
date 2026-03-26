(function (gfFieldFilterUI, $) {

    $.fn.gfFilterUI = function(filterSettings, initVars, allowMultiple, minResizeHeight) {
        init(this, filterSettings, initVars, allowMultiple, minResizeHeight );
        return this;
    };

    // private
    var $container, operatorStrings, settings, filters, mode, imagesURL, isResizable, allowMultiple, height;

    function init (c, s, initVars, m, h){
        $container = $(c);
        $container
            .css('position' , 'relative')
            .html('<div id="kdnaform-field-filters"></div>');
        height = h;
        isResizable = typeof height != 'undefined' && height > 0;
        operatorStrings = {"is":"is","isnot":"isNot", ">":"greaterThan", "<":"lessThan", "contains":"contains", "starts_with":"startsWith", "ends_with":"endsWith"};
        imagesURL = kdna_vars.baseUrl + "/images";
        settings = s;
        filters = initVars && initVars.filters ? initVars.filters : [];
        mode = initVars && initVars.mode ? initVars.mode : "all";
        allowMultiple = typeof m == 'undefined' || m ? true : false ;

        setUpFilters(filters);

    }

    function setUpFilters(filters) {
        var i;

        $container.on('change', '.kdnaform-filter-field', function(){
            changeField(this);
        });

		$container.on( 'click', '#kdnaform-no-filters', function() {
			if ( $( '.kdnaform-field-filter' ).length == 0 ) {
				addNewFieldFilter( this );
			}
			$( this ).remove();
		});

		$container.on( 'click', '.kdnaform-add', function( e ) {
			addNewFieldFilter( this );
			e.preventDefault();
		});

        $container.on('click', '.kdnaform-remove', function(){
            removeFieldFilter(this);
        });

        $container.on('change', '.kdnaform-filter-operator', function(){
            changeOperator(this, this.value);
        });

        if (typeof filters == 'undefined' || filters.length == 0){
            displayNoFiltersMessage();
            return;
        }

        if(mode != "off"){
            $("#kdnaform-field-filters").append(getFilterMode(mode));
        }

        for (i = 0; i < filters.length; i++) {
            $("#kdnaform-field-filters").append(getNewFilterRow());
        }


        $(".kdnaform-filter-field").each(function (i) {
            var fieldId = filters[i].field;
            jQuery(this).val(fieldId);
            changeField(this);
        });
        $(".kdnaform-filter-operator").each(function (i) {
            var operator = filters[i].operator;
            jQuery(this).val(operator);
            changeOperator(this, this.value);
        });

        $(".kdnaform-filter-value").each(function (i) {
            var value = filters[i].value;
            jQuery(this).val(value);
            jQuery(this).change();
        });

        maybeMakeResizable()
    }

    function getNewFilterRow() {
        var str;
        str = "<div class='kdnaform-field-filter'>";
        str += getFilterFields() + getFilterOperators() + getFilterValues() + getAddRemoveButtons();
        str += "</div>";
        return str;
    }

    function getFilterFields() {
        var i, select = [], optionsHTML;
        select.push("<select class='kdnaform-filter-field' name='f[]' >");
        for (i = 0; i < settings.length; i++) {
			optionsHTML = getOptions( settings[i] );
            select.push( optionsHTML );

        }
        select.push("</select>");
        select.push("<input type='hidden' class='kdnaform-filter-type' name='t[]' value='' >");
        return select.join('');
    }

    function getOptions(setting, depth) {
    	if ( ! depth ) {
			depth = 0;
		}
		var j, key, val, label, groupLabel, options, disabled = "", numRows,
			select = [], subFilter, subFilterGroup, newDepth, indent, indentString = '&nbsp;&nbsp;&nbsp;&nbsp;';
		key = setting.key;

		if (setting.group) {
			numRows = setting.filters.length;
			options = [];
			newDepth = setting.isNestable ? depth + 1 : depth;
			for (j = 0; j < numRows; j++) {
				subFilter = setting.filters[j];
				if (subFilter.group) {
					subFilterGroup = getOptions(subFilter, newDepth);
					options.push(subFilterGroup);
					continue;
				}
				indent = indentString.repeat(newDepth);
				label = indent + subFilter.text;
				val = subFilter.key;
				disabled = isFieldSelected(val) ? 'disabled="disabled"' : "";
				options.push('<option {0} value="{1}">{2}</option>'.gformFormat(disabled, val, label));
			}
			indent = indentString.repeat(depth);
			groupLabel = indent + setting.text;
			if ( setting.isNestable ) {
				// Optgroups can't be nested so close the optgroup immediately and fake the nested options with indentation.
				select.push('<optgroup label="{0}"></optgroup>{1}'.gformFormat(groupLabel, options.join('')));
			} else {
				select.push('<optgroup label="{0}">{1}</optgroup>'.gformFormat(groupLabel, options.join('')));
			}
		} else {
			disabled = setting.preventMultiple && isFieldSelected(key) ? "disabled='disabled'" : "";
			label = setting.text;
			select.push('<option {0} value="{1}">{2}</option>'.gformFormat(disabled, key, label));
		}
		return select.join('');
	}

    function changeOperator (operatorSelect) {
        var $select = $(operatorSelect);
        var $fieldSelect = $select.siblings('.kdnaform-filter-field');
        var filter = getFilter($fieldSelect.val());
        if (filter) {
            $select.siblings(".kdnaform-filter-value").replaceWith(getFilterValues(filter, operatorSelect.value));
        }
        setDisabledFields();
        if(window['gformInitDatepicker']) {gformInitDatepicker();}
    }

    function changeField (fieldSelect) {
        var filter = getFilter(fieldSelect.value);
        if (filter) {
            var $select = $(fieldSelect);
            $select.siblings(".kdnaform-filter-value").replaceWith(getFilterValues(filter));
            $select.siblings(".kdnaform-filter-type").val(filter.type);
            $select.siblings(".kdnaform-filter-operator").replaceWith(getFilterOperators(filter));
            $select.siblings(".kdnaform-filter-operator").change();
        }
        setDisabledFields();
    }

    function isFieldSelected (fieldId) {
        fieldId = fieldId.toString();
        var selectedFields = [];
        $('.kdnaform-filter-field :selected').each(function (i, selected) {
            selectedFields[i] = $(selected).val();
        });
        return $.inArray(fieldId, selectedFields) > -1 ? true : false;
    }

    function getFilterOperators (filter) {
        var i, operator,
            str = "<select name='o[]' class='kdnaform-filter-operator'>";
        if (filter) {
            for (i = 0; i < filter.operators.length; i++) {
                operator = filter.operators[i];
                str += '<option value="{0}">{1}</option>'.gformFormat(operator, kdna_vars[operatorStrings[operator]] );
            }
        }
        str += "</select>";
        return str;
    }

    function getFilterValues (filter, selectedOperator) {
        var i, val, text, str, options = "", placeholder, cssClass, supporterOperators;
        cssClass = 'kdnaform-filter-value';

        if ( filter && typeof filter.cssClass != 'undefined' ) {
            cssClass += ' ' + filter.cssClass;
        }

        if ( filter && filter.values && selectedOperator != 'contains' ) {

            if ( typeof filter.placeholder != 'undefined' ){
                options += '<option value="">{0}</option>'.gformFormat(filter.placeholder);
            }

            for (i = 0; i < filter.values.length; i++) {
                val = filter.values[i].value;
                text = filter.values[i].text;
                if ( filter.values[i].operators && $.inArray( selectedOperator, filter.values[i].operators ) === -1 ) {
                    continue;
                }
                options += '<option value="{0}">{1}</option>'.gformFormat(val, text);
            }
            str = "<select name='v[]' class='{0}'>{1}</select>".gformFormat(cssClass, options);
        } else {
            placeholder = ( filter && typeof filter.placeholder != 'undefined' ) ? "placeholder='{0}'".gformFormat(filter.placeholder) : '';

            str = "<input type='text' value='' name='v[]' class='{0}' {1}/>".gformFormat(cssClass, placeholder);
        }

        return str;
    }


    function getFilter (key, group) {
    	var f;

        if (!key) {
			return;
		}

        if (!group) {
			group = settings;
		}

        for (var i = 0; i < group.length; i++) {
            if (key == group[i].key) {
				return group[i];
			} else if (group[i].group) {
				f = getFilter(key, group[i].filters);
				if ( f ) {
					return f;
				}
			}
        }
    }

    function getAddRemoveButtons () {
        var str = "";
        if(!allowMultiple)
            return str;

        str += "<button " +
	        "class='kdnaform-add add_field_choice kdnaform-st-icon kdnaform-st-icon--circle-plus' " +
	        "title='{0}'" +
	        "></button>".gformFormat(kdna_vars.addFieldFilter);
        str += "<button " +
	        "class='kdnaform-remove delete_field_choice kdnaform-st-icon kdnaform-st-icon--circle-minus' " +
	        "title='" + kdna_vars.removeFieldFilter + "'" +
	        "></button>";
        return str;
    }

    function maybeMakeResizable () {
        if(!isResizable)
            return;

        var $filterBox = $("#kdnaform-field-filters");

        var $filters = $(".kdnaform-field-filter");

        if ($filters.length <= 1) {
            if ($($container).hasClass('ui-resizable'))
                $container.resizable('destroy');
            return;
        }
        var makeResizable = ($filterBox.get(0).scrollHeight > $container.height()) || $container.height() >= height;

        if (makeResizable) {
            $container
                .css({'min-height': height + 'px' , 'border-bottom': '5px double #DDD'})
                .resizable({
                    handles  : 's',
                    minHeight: height
                });
            $filterBox.css("min-height", height);
        } else {
            $container.css({'min-height': '', 'border-bottom': ''});
        }
    }

    function displayNoFiltersMessage () {
        var str = "";
        str += "<div id='kdnaform-no-filters' >" + kdna_vars.addFieldFilter;
        str += "<button " +
	        "class='kdnaform-add add_field_choice kdnaform-st-icon kdnaform-st-icon--circle-plus' " +
	        "title='{0}'" +
	        "></div>".gformFormat(kdna_vars.addFieldFilter);
        $("#kdnaform-field-filters").html(str);
        if(isResizable){
            $container.css({'min-height': '', 'border-bottom': ''});
            $container.height(80);
            $("#kdnaform-field-filters").css("min-height", '');
        }

    }

    function setDisabledFields () {
        $("select.kdnaform-filter-field option").removeAttr("disabled");
        $("select.kdnaform-filter-field").each(function (i) {
            var filter = getFilter(this.value);
            if (typeof(filter) != 'undefined' && filter.preventMultiple && isFieldSelected(this.value)) {
                $("select.kdnaform-filter-field option[value='" + this.value + "']:not(:selected)").attr('disabled', 'disabled');
            }
        });

    }

    function getFilterMode(mode){
        var html;
        html = '<select name="mode"><option value="all" {0}>{1}</option><option value="any" {2}>{3}</option></select>'.gformFormat(selected("all", mode), kdna_vars.all, selected("any", mode), kdna_vars.any);
        html = kdna_vars.filterAndAny.gformFormat(html);
        return html
    }

    function selected(selected, current){
        return selected == current ? 'selected="selected"' : "";
    }

    function addFilterMode ($filterRow) {

        $filterRow.after(getFilterMode());
    }

	function addNewFieldFilter ( el ) {
		var $el, $filterRow;
		$el = $( el );
		if ( $el.is( "button" ) ) {
			$filterRow = $el.parent();
		} else {
			$filterRow = $el;
		}

		$filterRow.after( getNewFilterRow() );
		$filterRow.next( "div" )
			.find( ".kdnaform-filter-field" ).change()
			.find( ".kdnaform-filter-operator" ).change();
		if ($( ".kdnaform-field-filter" ).length == 1 ) {
			addFilterMode( $filterRow );
		}

		maybeMakeResizable();
	}

    function removeFieldFilter (img) {
        $(img).parent().remove();
        if ($(".kdnaform-field-filter").length == 0)
            displayNoFiltersMessage();
        setDisabledFields();
        maybeMakeResizable();
    }

	if ( ! String.prototype.gformFormat ) {
		String.prototype.gformFormat = function() {
			var args = arguments;
			return this.replace( /{(\d+)}/g, function( match, number ) {
				return typeof args[ number ] != 'undefined' ? args[ number ] : match;
			} );
		};
	}

}(window.gfFilterUI = window.gfFilterUI || {}, jQuery));
