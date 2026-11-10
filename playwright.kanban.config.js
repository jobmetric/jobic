import {defineConfig} from '@playwright/test';
export default defineConfig({
 testDir:'./tests/Browser',testMatch:'kanban.spec.js',workers:1,timeout:30000,expect:{timeout:8000},
 reporter:[['list']],use:{baseURL:'http://127.0.0.1:5184',trace:'retain-on-failure'},
 webServer:{command:'node tests/Browser/kanban-server.mjs',url:'http://127.0.0.1:5184/admin/posts/post/kanban',reuseExistingServer:false,timeout:15000},
});
