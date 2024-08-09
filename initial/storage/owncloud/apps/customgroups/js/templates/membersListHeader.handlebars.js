(function() {
  var template = Handlebars.template, templates = OCA.CustomGroups.Templates = OCA.CustomGroups.Templates || {};
templates['membersListHeader'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "		<input class=\"action-leave-group\" type=\"button\" value=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"leaveGroupLabel") || (depth0 != null ? lookupProperty(depth0,"leaveGroupLabel") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"leaveGroupLabel","hash":{},"data":data,"loc":{"start":{"line":11,"column":57},"end":{"line":11,"column":76}}}) : helper)))
    + "\" />\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "		<form class=\"custom-group-import-form\" enctype=\"multipart/form-data\" method=\"post\" name=\"custom-group-csv-import\">\n			<a class=\"button action-export-csv\" href=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"downloadUrl") || (depth0 != null ? lookupProperty(depth0,"downloadUrl") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"downloadUrl","hash":{},"data":data,"loc":{"start":{"line":15,"column":45},"end":{"line":15,"column":60}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"exportCsvLabel") || (depth0 != null ? lookupProperty(depth0,"exportCsvLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"exportCsvLabel","hash":{},"data":data,"loc":{"start":{"line":15,"column":62},"end":{"line":15,"column":80}}}) : helper)))
    + "</a>\n			<label for=\"custom-group-import-elem\" class=\"custom-group-import-label button\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"importCsvLabel") || (depth0 != null ? lookupProperty(depth0,"importCsvLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"importCsvLabel","hash":{},"data":data,"loc":{"start":{"line":16,"column":82},"end":{"line":16,"column":100}}}) : helper)))
    + "</label>\n			<input id=\"custom-group-import-elem\" name=\"csv-input\" type=\"file\" accept=\"text/csv\" />\n		</form>\n";
},"5":function(container,depth0,helpers,partials,data) {
    return "	<div class=\"add-member-container\"></div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div>\n	<div class=\"group-name-title\">\n		<span class=\"avatar\"></span>\n		<span class=\"group-name-title-display\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"groupName") || (depth0 != null ? lookupProperty(depth0,"groupName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"groupName","hash":{},"data":data,"loc":{"start":{"line":4,"column":41},"end":{"line":4,"column":54}}}) : helper)))
    + "</span>\n	</div>\n\n	<a class=\"close action-close icon-close\" href=\"#\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"closeLabel") || (depth0 != null ? lookupProperty(depth0,"closeLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"closeLabel","hash":{},"data":data,"loc":{"start":{"line":7,"column":58},"end":{"line":7,"column":72}}}) : helper)))
    + "\"></a>\n</div>\n<div class=\"custom-group-buttons\">\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"userIsMember") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":10,"column":1},"end":{"line":12,"column":8}}})) != null ? stack1 : "")
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"canAdmin") : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":13,"column":1},"end":{"line":19,"column":8}}})) != null ? stack1 : "")
    + "</div>\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"canAdmin") : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":21,"column":0},"end":{"line":23,"column":7}}})) != null ? stack1 : "");
},"useData":true});
})();
