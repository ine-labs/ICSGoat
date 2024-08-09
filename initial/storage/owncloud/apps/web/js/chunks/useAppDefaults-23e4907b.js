define(["exports","./vendor-569e5121","./useRoute-1812bd9a","./cache-40bfe919","./resources-a6033be8"],(function(e,t,i,n,o){"use strict";function r(e){const i=e.store,n=e.applicationName;return{applicationConfig:t.computed((()=>{const e=i.state.apps.fileEditors.find((e=>e.app===n));if(!e)throw new Error(`useAppConfig: could not find config for applicationName: ${n}`);return e.config||{}}))}}function l(e){const i=(e.clientService||n.clientService).owncloudSdk,o=e.isPublicLinkContext,r=e.publicLinkPassword;return{getFileContents:async(e,n)=>{if(t.unref(o)){const o=await i.publicFiles.download("",e,t.unref(r));o.statusCode=o.status;const l=["arrayBuffer","blob","text"].includes(n?.responseType)?n.responseType:"text";return{response:o,body:await o[l](),headers:{ETag:o.headers.get("etag"),"OC-FileId":o.headers.get("oc-fileid")}}}return i.files.getFileContents(e,{resolveWithResponseObject:!0,...n})},getUrlForResource:({webDavPath:e,downloadURL:n},r=null)=>{const l=t.lib.stringify(r);if(t.unref(o)){if(!n){const t=["public-files",e].join("/");return[i.files.getFileUrl(t),l].filter(Boolean).join("?")}const[t,o]=n.split("?");return[t,[l,o].filter(Boolean).join("&")].filter(Boolean).join("?")}return[i.files.getFileUrl(e),l].filter(Boolean).join("?")},getFileInfo:async(e,n)=>t.unref(o)?await i.publicFiles.getFileInfo(e,t.unref(r),n):i.files.fileInfo(e,n),putFileContents:(e,n,l)=>t.unref(o)?i.publicFiles.putFileContents("",e,t.unref(r),n,l):i.files.putFileContents(e,n,l)}}function s(e){const i=(e.clientService||n.clientService).owncloudSdk,r=e.store,l=e.isPublicLinkContext,s=e.publicLinkPassword,u=t.ref(!1),c=t.computed((()=>r.getters["Files/activeFiles"]));return{isFolderLoading:u,loadFolderForFileContext:e=>{const{path:c}=t.unref(e);return(async e=>{if(""===r.getters.activeFile.path){u.value=!0,r.commit("Files/CLEAR_CURRENT_FILES_LIST",null);try{const u=t.unref(l)?i.publicFiles.list(e,t.unref(s),n.DavProperties.PublicLink):i.files.list(e,1,n.DavProperties.Default);let c=await u;c=c.map(o.buildResource),r.commit("Files/LOAD_FILES",{currentFolder:c[0],files:c.slice(1)})}catch(e){r.commit("Files/SET_CURRENT_FOLDER",null),console.error(e)}u.value=!1}})(t.dirname(t.unref(c)))},activeFiles:c}}e.useAppDefaults=function(e){const u=i.useRouter(),c=n.useStore(),a=i.useRoute(),f=e.clientService||n.clientService,p=t.computed((()=>"files-public-files"===t.unref(a).query[o.contextRouteNameKey])),d=t.computed((()=>c.getters["Files/publicLinkPassword"])),F=t.computed((()=>{return{path:`/${t.unref(a).params.filePath.split("/").filter(Boolean).join("/")}`,routeName:(e=t.unref(a).query[o.contextRouteNameKey],Array.isArray(e)?e[0]:e),...o.contextQueryToFileContextProps(t.unref(a).query)};var e}));return{isPublicLinkContext:p,currentFileContext:F,...r({store:c,...e}),...o.useAppNavigation({router:u,currentFileContext:F}),...l({clientService:f,store:c,isPublicLinkContext:p,publicLinkPassword:d}),...s({clientService:f,store:c,isPublicLinkContext:p,publicLinkPassword:d})}}}));