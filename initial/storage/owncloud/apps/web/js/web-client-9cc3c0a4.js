define(["exports","./chunks/vendor-569e5121"],(function(e,t){"use strict";const r="https://ocis.ocis-traefik.latest.owncloud.works".replace(/\/+$/,""),s=",";class n extends Error{field;name="RequiredError";constructor(e,t){super(t),this.field=e}}const o="https://example.com",a=function(e,t,r){if(null==r)throw new n(t,`Required parameter ${t} was null or undefined when calling ${e}.`)},i=function(e,...t){const r=new URLSearchParams(e.search);for(const e of t)for(const t in e)if(Array.isArray(e[t])){r.delete(t);for(const s of e[t])r.append(t,s)}else r.set(t,e[t]);e.search=r.toString()},d=function(e,t,r){const s="string"!=typeof e;return(s&&r&&r.isJsonMime?r.isJsonMime(t.headers["Content-Type"]):s)?JSON.stringify(void 0!==e?e:{}):e||""},c=function(e){return e.pathname+e.search+e.hash},u=function(e,t,r,s){return(n=t,o=r)=>{const a={...e.options,url:(s?.basePath||o)+e.url};return n.request(a)}},p=function(e){const n=function(e){return{createDrive:async(t,r={})=>{a("createDrive","drive",t);const s=new URL("/drives",o);let n;e&&(n=e.baseOptions);const u={method:"POST",...n,...r},p={"Content-Type":"application/json"};i(s,{});let l=n&&n.headers?n.headers:{};return u.headers={...p,...l,...r.headers},u.data=d(t,u,e),{url:c(s),options:u}},deleteDrive:async(t,r,s={})=>{a("deleteDrive","driveId",t);const n="/drives/{drive-id}".replace("{drive-id}",encodeURIComponent(String(t))),d=new URL(n,o);let u;e&&(u=e.baseOptions);const p={method:"DELETE",...u,...s},l={};null!=r&&(l["If-Match"]=String(r)),i(d,{});let h=u&&u.headers?u.headers:{};return p.headers={...l,...h,...s.headers},{url:c(d),options:p}},getDrive:async(t,r,n,d={})=>{a("getDrive","driveId",t);const u="/drives/{drive-id}".replace("{drive-id}",encodeURIComponent(String(t))),p=new URL(u,o);let l;e&&(l=e.baseOptions);const h={method:"GET",...l,...d},m={};r&&(m.$select=Array.from(r).join(s)),n&&(m.$expand=Array.from(n).join(s)),i(p,m);let y=l&&l.headers?l.headers:{};return h.headers={...y,...d.headers},{url:c(p),options:h}},updateDrive:async(t,r,s={})=>{a("updateDrive","driveId",t),a("updateDrive","drive",r);const n="/drives/{drive-id}".replace("{drive-id}",encodeURIComponent(String(t))),u=new URL(n,o);let p;e&&(p=e.baseOptions);const l={method:"PATCH",...p,...s},h={"Content-Type":"application/json"};i(u,{});let m=p&&p.headers?p.headers:{};return l.headers={...h,...m,...s.headers},l.data=d(r,l,e),{url:c(u),options:l}}}}(e);return{async createDrive(s,o){const a=await n.createDrive(s,o);return u(a,t.axios,r,e)},async deleteDrive(s,o,a){const i=await n.deleteDrive(s,o,a);return u(i,t.axios,r,e)},async getDrive(s,o,a,i){const d=await n.getDrive(s,o,a,i);return u(d,t.axios,r,e)},async updateDrive(s,o,a){const i=await n.updateDrive(s,o,a);return u(i,t.axios,r,e)}}},l=function(e){const n=function(e){return{addMember:async(t,r,s={})=>{a("addMember","groupId",t),a("addMember","memberReference",r);const n="/groups/{group-id}/members/$ref".replace("{group-id}",encodeURIComponent(String(t))),u=new URL(n,o);let p;e&&(p=e.baseOptions);const l={method:"POST",...p,...s},h={"Content-Type":"application/json"};i(u,{});let m=p&&p.headers?p.headers:{};return l.headers={...h,...m,...s.headers},l.data=d(r,l,e),{url:c(u),options:l}},deleteGroup:async(t,r,s={})=>{a("deleteGroup","groupId",t);const n="/groups/{group-id}".replace("{group-id}",encodeURIComponent(String(t))),d=new URL(n,o);let u;e&&(u=e.baseOptions);const p={method:"DELETE",...u,...s},l={};null!=r&&(l["If-Match"]=String(r)),i(d,{});let h=u&&u.headers?u.headers:{};return p.headers={...l,...h,...s.headers},{url:c(d),options:p}},deleteMember:async(t,r,s,n={})=>{a("deleteMember","groupId",t),a("deleteMember","directoryObjectId",r);const d="/groups/{group-id}/members/{directory-object-id}/$ref".replace("{group-id}",encodeURIComponent(String(t))).replace("{directory-object-id}",encodeURIComponent(String(r))),u=new URL(d,o);let p;e&&(p=e.baseOptions);const l={method:"DELETE",...p,...n},h={};null!=s&&(h["If-Match"]=String(s)),i(u,{});let m=p&&p.headers?p.headers:{};return l.headers={...h,...m,...n.headers},{url:c(u),options:l}},getGroup:async(t,r,n={})=>{a("getGroup","groupId",t);const d="/groups/{group-id}".replace("{group-id}",encodeURIComponent(String(t))),u=new URL(d,o);let p;e&&(p=e.baseOptions);const l={method:"GET",...p,...n},h={};r&&(h.$select=Array.from(r).join(s)),i(u,h);let m=p&&p.headers?p.headers:{};return l.headers={...m,...n.headers},{url:c(u),options:l}},updateGroup:async(t,r,s={})=>{a("updateGroup","groupId",t),a("updateGroup","group",r);const n="/groups/{group-id}".replace("{group-id}",encodeURIComponent(String(t))),u=new URL(n,o);let p;e&&(p=e.baseOptions);const l={method:"PATCH",...p,...s},h={"Content-Type":"application/json"};i(u,{});let m=p&&p.headers?p.headers:{};return l.headers={...h,...m,...s.headers},l.data=d(r,l,e),{url:c(u),options:l}}}}(e);return{async addMember(s,o,a){const i=await n.addMember(s,o,a);return u(i,t.axios,r,e)},async deleteGroup(s,o,a){const i=await n.deleteGroup(s,o,a);return u(i,t.axios,r,e)},async deleteMember(s,o,a,i){const d=await n.deleteMember(s,o,a,i);return u(d,t.axios,r,e)},async getGroup(s,o,a){const i=await n.getGroup(s,o,a);return u(i,t.axios,r,e)},async updateGroup(s,o,a){const i=await n.updateGroup(s,o,a);return u(i,t.axios,r,e)}}},h=function(e){const n=function(e){return{createGroup:async(t,r={})=>{a("createGroup","group",t);const s=new URL("/groups",o);let n;e&&(n=e.baseOptions);const u={method:"POST",...n,...r},p={"Content-Type":"application/json"};i(s,{});let l=n&&n.headers?n.headers:{};return u.headers={...p,...l,...r.headers},u.data=d(t,u,e),{url:c(s),options:u}},listGroups:async(t,r,n,a,d,u,p,l={})=>{const h=new URL("/groups",o);let m;e&&(m=e.baseOptions);const y={method:"GET",...m,...l},v={};void 0!==t&&(v.$top=t),void 0!==r&&(v.$skip=r),void 0!==n&&(v.$search=n),void 0!==a&&(v.$filter=a),void 0!==d&&(v.$count=d),u&&(v.$orderby=Array.from(u).join(s)),p&&(v.$select=Array.from(p).join(s)),i(h,v);let g=m&&m.headers?m.headers:{};return y.headers={...g,...l.headers},{url:c(h),options:y}}}}(e);return{async createGroup(s,o){const a=await n.createGroup(s,o);return u(a,t.axios,r,e)},async listGroups(s,o,a,i,d,c,p,l){const h=await n.listGroups(s,o,a,i,d,c,p,l);return u(h,t.axios,r,e)}}},m=function(e){const n=function(e){return{listMyDrives:async(t,r,n,a,d,u,p,l={})=>{const h=new URL("/me/drives",o);let m;e&&(m=e.baseOptions);const y={method:"GET",...m,...l},v={};void 0!==t&&(v.$top=t),void 0!==r&&(v.$skip=r),void 0!==n&&(v.$orderby=n),void 0!==a&&(v.$filter=a),void 0!==d&&(v.$count=d),u&&(v.$select=Array.from(u).join(s)),p&&(v.$expand=Array.from(p).join(s)),i(h,v);let g=m&&m.headers?m.headers:{};return y.headers={...g,...l.headers},{url:c(h),options:y}}}}(e);return{async listMyDrives(s,o,a,i,d,c,p,l){const h=await n.listMyDrives(s,o,a,i,d,c,p,l);return u(h,t.axios,r,e)}}};class y extends class{basePath;axios;configuration;constructor(e,s=r,n=t.axios){this.basePath=s,this.axios=n,e&&(this.configuration=e,this.basePath=e.basePath||this.basePath)}}{listMyDrives(e,t,r,s,n,o,a,i){return m(this.configuration).listMyDrives(e,t,r,s,n,o,a,i).then((e=>e(this.axios,this.basePath)))}}const v=function(e){const s=function(e){return{meGet:async(t={})=>{const r=new URL("/me",o);let s;e&&(s=e.baseOptions);const n={method:"GET",...s,...t};i(r,{});let a=s&&s.headers?s.headers:{};return n.headers={...a,...t.headers},{url:c(r),options:n}}}}(e);return{async meGet(n){const o=await s.meGet(n);return u(o,t.axios,r,e)}}},g=function(e){const n=function(e){return{deleteUser:async(t,r,s={})=>{a("deleteUser","userId",t);const n="/users/{user-id}".replace("{user-id}",encodeURIComponent(String(t))),d=new URL(n,o);let u;e&&(u=e.baseOptions);const p={method:"DELETE",...u,...s},l={};null!=r&&(l["If-Match"]=String(r)),i(d,{});let h=u&&u.headers?u.headers:{};return p.headers={...l,...h,...s.headers},{url:c(d),options:p}},getUser:async(t,r,n,d={})=>{a("getUser","userId",t);const u="/users/{user-id}".replace("{user-id}",encodeURIComponent(String(t))),p=new URL(u,o);let l;e&&(l=e.baseOptions);const h={method:"GET",...l,...d},m={};r&&(m.$select=Array.from(r).join(s)),n&&(m.$expand=Array.from(n).join(s)),i(p,m);let y=l&&l.headers?l.headers:{};return h.headers={...y,...d.headers},{url:c(p),options:h}},updateUser:async(t,r,s={})=>{a("updateUser","userId",t),a("updateUser","user",r);const n="/users/{user-id}".replace("{user-id}",encodeURIComponent(String(t))),u=new URL(n,o);let p;e&&(p=e.baseOptions);const l={method:"PATCH",...p,...s},h={"Content-Type":"application/json"};i(u,{});let m=p&&p.headers?p.headers:{};return l.headers={...h,...m,...s.headers},l.data=d(r,l,e),{url:c(u),options:l}}}}(e);return{async deleteUser(s,o,a){const i=await n.deleteUser(s,o,a);return u(i,t.axios,r,e)},async getUser(s,o,a,i){const d=await n.getUser(s,o,a,i);return u(d,t.axios,r,e)},async updateUser(s,o,a){const i=await n.updateUser(s,o,a);return u(i,t.axios,r,e)}}},f=function(e){const n=function(e){return{createUser:async(t,r={})=>{a("createUser","user",t);const s=new URL("/users",o);let n;e&&(n=e.baseOptions);const u={method:"POST",...n,...r},p={"Content-Type":"application/json"};i(s,{});let l=n&&n.headers?n.headers:{};return u.headers={...p,...l,...r.headers},u.data=d(t,u,e),{url:c(s),options:u}},listUsers:async(t,r,n,a,d,u,p,l,h={})=>{const m=new URL("/users",o);let y;e&&(y=e.baseOptions);const v={method:"GET",...y,...h},g={};void 0!==t&&(g.$top=t),void 0!==r&&(g.$skip=r),void 0!==n&&(g.$search=n),void 0!==a&&(g.$filter=a),void 0!==d&&(g.$count=d),u&&(g.$orderby=Array.from(u).join(s)),p&&(g.$select=Array.from(p).join(s)),l&&(g.$expand=Array.from(l).join(s)),i(m,g);let f=y&&y.headers?y.headers:{};return v.headers={...f,...h.headers},{url:c(m),options:v}}}}(e);return{async createUser(s,o){const a=await n.createUser(s,o);return u(a,t.axios,r,e)},async listUsers(s,o,a,i,d,c,p,l,h){const m=await n.listUsers(s,o,a,i,d,c,p,l,h);return u(m,t.axios,r,e)}}};class U{apiKey;username;password;accessToken;basePath;baseOptions;formDataCtor;constructor(e={}){this.apiKey=e.apiKey,this.username=e.username,this.password=e.password,this.accessToken=e.accessToken,this.basePath=e.basePath,this.baseOptions=e.baseOptions,this.formDataCtor=e.formDataCtor}isJsonMime(e){const t=new RegExp("^(application/json|[^;/ \t]+/[^;/ \t]+[+]json)[ \t]*(;.*)?$","i");return null!==e&&(t.test(e)||"application/json-patch+json"===e.toLowerCase())}}const b=(e,t)=>{const r=new URL("/graph/v1.0",e).href,s=new U({basePath:r}),n=new y(s,s.basePath,t),o=function(e,t,r){const s=v(e);return{meGet:e=>s.meGet(e).then((e=>e(r,t)))}}(s,s.basePath,t),a=function(e,t,r){const s=g(e);return{deleteUser:(e,n,o)=>s.deleteUser(e,n,o).then((e=>e(r,t))),getUser:(e,n,o,a)=>s.getUser(e,n,o,a).then((e=>e(r,t))),updateUser:(e,n,o)=>s.updateUser(e,n,o).then((e=>e(r,t)))}}(s,s.basePath,t),i=function(e,t,r){const s=f(e);return{createUser:(e,n)=>s.createUser(e,n).then((e=>e(r,t))),listUsers:(e,n,o,a,i,d,c,u,p)=>s.listUsers(e,n,o,a,i,d,c,u,p).then((e=>e(r,t)))}}(s,s.basePath,t),d=function(e,t,r){const s=l(e);return{addMember:(e,n,o)=>s.addMember(e,n,o).then((e=>e(r,t))),deleteGroup:(e,n,o)=>s.deleteGroup(e,n,o).then((e=>e(r,t))),deleteMember:(e,n,o,a)=>s.deleteMember(e,n,o,a).then((e=>e(r,t))),getGroup:(e,n,o)=>s.getGroup(e,n,o).then((e=>e(r,t))),updateGroup:(e,n,o)=>s.updateGroup(e,n,o).then((e=>e(r,t)))}}(s,s.basePath,t),c=function(e,t,r){const s=h(e);return{createGroup:(e,n)=>s.createGroup(e,n).then((e=>e(r,t))),listGroups:(e,n,o,a,i,d,c,u)=>s.listGroups(e,n,o,a,i,d,c,u).then((e=>e(r,t)))}}(s,s.basePath,t),u=function(e,t,r){const s=p(e);return{createDrive:(e,n)=>s.createDrive(e,n).then((e=>e(r,t))),deleteDrive:(e,n,o)=>s.deleteDrive(e,n,o).then((e=>e(r,t))),getDrive:(e,n,o,a)=>s.getDrive(e,n,o,a).then((e=>e(r,t))),updateDrive:(e,n,o)=>s.updateDrive(e,n,o).then((e=>e(r,t)))}}(s,s.basePath,t);return{drives:{listMyDrives:(e,t)=>n.listMyDrives(0,0,e,t),getDrive:e=>u.getDrive(e),createDrive:(e,t)=>u.createDrive(e,t),updateDrive:(e,t,r)=>u.updateDrive(e,t,r),deleteDrive:(e,t,r)=>u.deleteDrive(e,t,r)},users:{getUser:e=>a.getUser(e),createUser:e=>i.createUser(e),getMe:()=>o.meGet(),deleteUser:e=>a.deleteUser(e),listUsers:e=>i.listUsers(0,0,"","",!1,new Set([e]))},groups:{createGroup:e=>c.createGroup(e),deleteGroup:e=>d.deleteGroup(e),listGroups:e=>c.listGroups(0,0,"","",!1,new Set([e]))}}};e.client=(e,r)=>{const s=t.axios.create({headers:{authorization:"Bearer "+r,"Content-Type":"application/x-www-form-urlencoded"}});return s.interceptors.request.use((e=>(e.headers["Content-Type"]="application/x-www-form-urlencoded",e))),{graph:b(e,s)}},Object.defineProperty(e,"__esModule",{value:!0})}));