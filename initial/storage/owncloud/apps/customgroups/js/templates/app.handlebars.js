(function() {
  var template = Handlebars.template, templates = OCA.CustomGroups.Templates = OCA.CustomGroups.Templates || {};
templates['app'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<h2>"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"customGroupsTitle") || (depth0 != null ? lookupProperty(depth0,"customGroupsTitle") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"customGroupsTitle","hash":{},"data":data,"loc":{"start":{"line":1,"column":4},"end":{"line":1,"column":25}}}) : helper)))
    + "</h2>\n<div class=\"groups-container icon-loading\"></div>\n<div class=\"members-container sidebar disappear\" id=\"app-sidebar\"></div>\n\n";
},"useData":true});
})();
