import Vue from "vue";
import Router from "vue-router";
import CONF from './config';
import { frontAuth, backAuth, devAuth } from '@/_oad_repo/auth';
import beforeEachRoute from '@/_oad_repo/sys/beforeEachRoute'
import afterEachRoute from '@/_oad_repo/sys/afterEachRoute'
import pathThrough from '@/_oad_repo/components/parentBlankComponent' 


Vue.use(Router);

// create new router
const routes = [
    {
        path: "/",
        component: require('@/views/frontend/layout').default,
        beforeEnter: frontAuth,
        children: [
            {
                path: "/",
                component: require('@/views/frontend/auth/login').default,
                name: 'login',
                meta: { title: 'Login | ' + CONF.APP_NAME },
            },
            {
                path: "/auth/forgot_password",
                name: 'forgot_password',
                component: require('@/views/frontend/auth/forgot_password').default,
                meta: { title: 'Forgot Password | ' + CONF.APP_NAME },
            },
            {
                path: "/auth/reset_password",
                name: 'reset_password',
                component: require('@/views/frontend/auth/reset_password').default,
                meta: { title: 'Reset Password | ' + CONF.APP_NAME },
            },
            {
                path: "/auth/reset_password_link/:email/:token",
                name: 'reset_password_link',
                component: require('@/views/frontend/auth/reset_password').default,
                meta: { title: 'Reset Password | ' + CONF.APP_NAME },
            },
            {
                path: "/auth/reset_password_link/expired",
                name: 'reset_password_link_expired',
                component: require('@/views/frontend/auth/reset_password_expired').default,
                meta: { title: 'Reset Password | ' + CONF.APP_NAME },
            }
        ]
    },
    {
        path: "/dev",
        component:  require('@/views/backend/common/layout').default,
        beforeEnter: devAuth,
        children: [
            {
                path: "menu-builder",
                name: 'sectionsBuilder',
                component:  require('@/_oad_repo/views/dev/sectionsBuilder').default,
                meta: { title: 'Sections Builder | ' + CONF.APP_NAME },
            },
            {
                path: "vueRouterBuilder",
                name: 'vueRouterBuilder',
                component:  require('@/_oad_repo/views/dev/vueRouterBuilder').default,
                meta: { title: 'Vue Router Builder | ' + CONF.APP_NAME },
            },
        ]
    },
    {
        path: "/app",
        component: () => import('@/views/backend/common/layout'),
        beforeEnter: backAuth,
        children: [
{!! $content !!}
        ]
    },
    {
        path: "*",
        component: require('@/views/pages/notFound').default,
        meta: { title: 'Page Not Found | ' + CONF.APP_NAME },
    }
];

const router = new Router({
    mode: "history",
    linkActiveClass: "active",
    routes,
    scrollBehavior(to, from, savedPosition) {
    return {x: 0, y: 0};
}
});

router.beforeEach(beforeEachRoute);
router.afterEach(afterEachRoute);

export default router;
