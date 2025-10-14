import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'

/**
* @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController::show
* @route '/user/confirm-password'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/user/confirm-password',
} satisfies RouteDefinition<["get","head"]>

/**
* @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController::show
* @route '/user/confirm-password'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController::show
* @route '/user/confirm-password'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController::store
* @route '/user/confirm-password'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/user/confirm-password',
} satisfies RouteDefinition<["post"]>

/**
* @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController::store
* @route '/user/confirm-password'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController::store
* @route '/user/confirm-password'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController::store
* @route '/user/confirm-password'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController::store
* @route '/user/confirm-password'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const ConfirmPasswordController = { show, store }

export default ConfirmPasswordController