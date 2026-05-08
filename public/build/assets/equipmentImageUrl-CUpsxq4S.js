function e(r){const t=r.trim();return t===""?"":/^https?:\/\//i.test(t)||t.startsWith("/")?t:`/storage/${t.replace(/^\/+/,"")}`}export{e as r};
