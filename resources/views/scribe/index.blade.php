<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TaskFlow API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=PT+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-elements.style.css") }}" media="screen">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/docco.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
    <script type="module">
        import {CodeJar} from 'https://medv.io/codejar/codejar.js'
        window.CodeJar = CodeJar;
    </script>

            <script>
            var tryItOutBaseUrl = "http://localhost:8000";
            var useCsrf = Boolean();
            var csrfUrl = "/sanctum/csrf-cookie";
        </script>
        <script src="{{ asset("/vendor/scribe/js/tryitout-5.2.1.js") }}"></script>
        <style>
            .code-editor, .response-content {
                color: whitesmoke;
                background-color: transparent;
            }
            /*
             Problem: we want syntax highlighting for the Try It Out JSON body code editor
             However, the Try It Out area uses a dark background, while request and response samples
             (which are already highlighted) use a light background. HighlightJS can only use one theme per document.
             Our options:
             1. Change the bg of one. => No, it looks out of place on the page.
             2. Use the same highlighting for both. => Nope, one would be unreadable.
             3. Copy styles for a dark-bg h1js theme and prefix them for the CodeEditor, which is what we're doing.
             Since it's only JSON, we only need a few styles anyway.
             Styles taken from the Nord theme: https://github.com/highlightjs/highlight.js/blob/3997c9b430a568d5ad46d96693b90a74fc01ea7f/src/styles/nord.css#L2
             */
            .code-editor > .hljs-attr {
                color: #8FBCBB;
            }
            .code-editor > .hljs-string {
                color: #A3BE8C;
            }
            .code-editor > .hljs-number {
                color: #B48EAD;
            }
            .code-editor > .hljs-literal{
                color: #81A1C1;
            }

        </style>

        <script>
            function tryItOut(btnElement) {
                btnElement.disabled = true;

                let endpointId = btnElement.dataset.endpoint;

                let errorPanel = document.querySelector(`.tryItOut-error[data-endpoint=${endpointId}]`);
                errorPanel.hidden = true;
                let responsePanel = document.querySelector(`.tryItOut-response[data-endpoint=${endpointId}]`);
                responsePanel.hidden = true;

                let form = btnElement.form;
                let { method, path, hasjsonbody: hasJsonBody} = form.dataset;
                let body = {};
                if (hasJsonBody === "1") {
                    body = form.querySelector('.code-editor').textContent;
                } else if (form.dataset.hasfiles === "1") {
                    body = new FormData();
                    form.querySelectorAll('input[data-component=body]')
                        .forEach(el => {
                            if (el.type === 'file') {
                                if (el.files[0]) body.append(el.name, el.files[0])
                            } else body.append(el.name, el.value);
                        });
                } else {
                    form.querySelectorAll('input[data-component=body]').forEach(el => {
                        _.set(body, el.name, el.value);
                    });
                }

                const urlParameters = form.querySelectorAll('input[data-component=url]');
                urlParameters.forEach(el => (path = path.replace(new RegExp(`\\{${el.name}\\??}`), el.value)));

                const headers = Object.fromEntries(Array.from(form.querySelectorAll('input[data-component=header]'))
                    .map(el => [el.name, (el.dataset.prefix || '') + el.value]));

                const query = {}
                form.querySelectorAll('input[data-component=query]').forEach(el => {
                    _.set(query, el.name, el.value);
                });

                let preflightPromise = Promise.resolve();
                if (window.useCsrf && window.csrfUrl) {
                    preflightPromise = makeAPICall('GET', window.csrfUrl).then(() => {
                        headers['X-XSRF-TOKEN'] = getCookie('XSRF-TOKEN');
                    });
                }

                // content type has to be unset otherwise file upload won't work
                if (form.dataset.hasfiles === "1") {
                    delete headers['Content-Type'];
                }

                return preflightPromise.then(() => makeAPICall(method, path, body, query, headers, endpointId))
                    .then(([responseStatus, statusText, responseContent, responseHeaders]) => {
                        responsePanel.hidden = false;
                        responsePanel.querySelector(`.response-status`).textContent = responseStatus + " " + statusText ;

                        let contentEl = responsePanel.querySelector(`.response-content`);
                        if (responseContent === '') {
                            contentEl.textContent = contentEl.dataset.emptyResponseText;
                            return;
                        }

                        // Prettify it if it's JSON
                        let isJson = false;
                        try {
                            const jsonParsed = JSON.parse(responseContent);
                            if (jsonParsed !== null) {
                                isJson = true;
                                responseContent = JSON.stringify(jsonParsed, null, 4);
                            }
                        } catch (e) {}

                        // Replace HTML entities
                        responseContent = responseContent.replace(/[<>&]/g, (i) => '&#' + i.charCodeAt(0) + ';');

                        contentEl.innerHTML = responseContent;
                        isJson && window.hljs.highlightElement(contentEl);
                    })
                    .catch(err => {
                        console.log(err);
                        let errorMessage = err.message || err;
                        errorPanel.hidden = false;
                        errorPanel.querySelector(`.error-message`).textContent = errorMessage;
                    })
                    .finally(() => { btnElement.disabled = false } );
            }

            window.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.tryItOut-btn').forEach(el => {
                    el.addEventListener('click', () => tryItOut(el));
                });
            })
        </script>
    
</head>

<body>

    <script>
        function switchExampleLanguage(lang) {
            document.querySelectorAll(`.example-request`).forEach(el => el.style.display = 'none');
            document.querySelectorAll(`.example-request-${lang}`).forEach(el => el.style.display = 'initial');
            document.querySelectorAll(`.example-request-lang-toggle`).forEach(el => el.value = lang);
        }
    </script>

<script>
    function switchExampleResponse(endpointId, index) {
        document.querySelectorAll(`.example-response-${endpointId}`).forEach(el => el.style.display = 'none');
        document.querySelectorAll(`.example-response-${endpointId}-${index}`).forEach(el => el.style.display = 'initial');
        document.querySelectorAll(`.example-response-${endpointId}-toggle`).forEach(el => el.value = index);
    }


    /*
     * Requirement: a div with class `expansion-chevrons`
     *   (or `expansion-chevrons-solid` to use the solid version).
     * Also add the `expanded` class if your div is expanded by default.
     */
    function toggleExpansionChevrons(evt) {
        let elem = evt.currentTarget;

        let chevronsArea = elem.querySelector('.expansion-chevrons');
        const solid = chevronsArea.classList.contains('expansion-chevrons-solid');
        const newState = chevronsArea.classList.contains('expanded') ? 'expand' : 'expanded';
        if (newState === 'expanded') {
            const selector = solid ? '#expanded-chevron-solid' : '#expanded-chevron';
            const template = document.querySelector(selector);
            const chevron = template.content.cloneNode(true);
            chevronsArea.replaceChildren(chevron);
            chevronsArea.classList.add('expanded');
        } else {
            const selector = solid ? '#expand-chevron-solid' : '#expand-chevron';
            const template = document.querySelector(selector);
            const chevron = template.content.cloneNode(true);
            chevronsArea.replaceChildren(chevron);
            chevronsArea.classList.remove('expanded');
        }

    }

    /**
     * 1. Make sure the children are inside the parent element
     * 2. Add `expandable` class to the parent
     * 3. Add `children` class to the children.
     * 4. Wrap the default chevron SVG in a div with class `expansion-chevrons`
     *   (or `expansion-chevrons-solid` to use the solid version).
     *   Also add the `expanded` class if your div is expanded by default.
     */
    function toggleElementChildren(evt) {
        let elem = evt.currentTarget;
        let children = elem.querySelector(`.children`);
        if (!children) return;

        if (children.contains(event.target)) return;

        let oldState = children.style.display
        if (oldState === 'none') {
            children.style.removeProperty('display');
            toggleExpansionChevrons(evt);
        } else {
            children.style.display = 'none';
            toggleExpansionChevrons(evt);
        }

        evt.stopPropagation();
    }

    function highlightSidebarItem(evt = null) {
        if (evt && evt.oldURL) {
            let oldHash = new URL(evt.oldURL).hash.slice(1);
            if (oldHash) {
                let previousItem = window['sidebar'].querySelector(`#toc-item-${oldHash}`);
                previousItem.classList.remove('sl-bg-primary-tint');
                previousItem.classList.add('sl-bg-canvas-100');
            }
        }

        let newHash = location.hash.slice(1);
        if (newHash) {
            let item = window['sidebar'].querySelector(`#toc-item-${newHash}`);
            item.classList.remove('sl-bg-canvas-100');
            item.classList.add('sl-bg-primary-tint');
        }
    }

    addEventListener('DOMContentLoaded', () => {
        highlightSidebarItem();

        document.querySelectorAll('.code-editor').forEach(elem => CodeJar(elem, (editor) => {
            // highlight.js does not trim old tags,
            // which means highlighting doesn't update on type (only on paste)
            // See https://github.com/antonmedv/codejar/issues/18
            editor.textContent = editor.textContent
            return hljs.highlightElement(editor)
        }));

        document.querySelectorAll('.expandable').forEach(el => {
            el.addEventListener('click', toggleElementChildren);
        });

        document.querySelectorAll('details').forEach(el => {
            el.addEventListener('toggle', toggleExpansionChevrons);
        });
    });

    addEventListener('hashchange', highlightSidebarItem);
</script>

<div class="sl-elements sl-antialiased sl-h-full sl-text-base sl-font-ui sl-text-body sl-flex sl-inset-0">

    <div id="sidebar" class="sl-flex sl-overflow-y-auto sl-flex-col sl-sticky sl-inset-y-0 sl-pt-8 sl-bg-canvas-100 sl-border-r"
     style="width: calc((100% - 1800px) / 2 + 300px); padding-left: calc((100% - 1800px) / 2); min-width: 300px; max-height: 100vh">
    <div class="sl-flex sl-items-center sl-mb-5 sl-ml-4">
                <h4 class="sl-text-paragraph sl-leading-snug sl-font-prose sl-font-semibold sl-text-heading">
            TaskFlow API Documentation
        </h4>
    </div>

    <div class="sl-flex sl-overflow-y-auto sl-flex-col sl-flex-grow sl-flex-shrink">
        <div class="sl-overflow-y-auto sl-w-full sl-bg-canvas-100">
            <div class="sl-my-3">
                                    <div class="expandable">
                        <div title="Introduction" id="toc-item-introduction"
                             class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-4 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none">
                            <a href="#introduction"
                               class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0">Introduction</a>
                                                    </div>

                                            </div>
                                    <div class="expandable">
                        <div title="Authenticating requests" id="toc-item-authenticating-requests"
                             class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-4 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none">
                            <a href="#authenticating-requests"
                               class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0">Authenticating requests</a>
                                                    </div>

                                            </div>
                                    <div class="expandable">
                        <div title="Authentication" id="toc-item-authentication"
                             class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-4 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none">
                            <a href="#authentication"
                               class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0">Authentication</a>
                                                            <div class="sl-flex sl-items-center sl-text-xs expansion-chevrons">
                                    <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                         data-icon="chevron-right"
                                         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
                                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                        <path fill="currentColor"
                                              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
                                    </svg>
                                </div>
                                                    </div>

                                                    <div class="children" style="display: none;">
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-authentication-POSTapi-v1-sign-up">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Sign Up">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#authentication-POSTapi-v1-sign-up">
                                                    Sign Up
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-authentication-POSTapi-v1-sign-in">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Sign In">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#authentication-POSTapi-v1-sign-in">
                                                    Sign In
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-authentication-POSTapi-v1-sign-out">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Sign Out">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#authentication-POSTapi-v1-sign-out">
                                                    Sign Out
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-authentication-POSTapi-v1-change-password">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Change Password">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#authentication-POSTapi-v1-change-password">
                                                    Change Password
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-authentication-GETapi-v1-email-verify--id---hash-">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Verify Email">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#authentication-GETapi-v1-email-verify--id---hash-">
                                                    Verify Email
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-authentication-POSTapi-v1-email-verification-notification">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Resend Email Verification">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#authentication-POSTapi-v1-email-verification-notification">
                                                    Resend Email Verification
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-authentication-POSTapi-v1-forgot-password">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Send Password Reset Link">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#authentication-POSTapi-v1-forgot-password">
                                                    Send Password Reset Link
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-authentication-POSTapi-v1-reset-password">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Reset Password">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#authentication-POSTapi-v1-reset-password">
                                                    Reset Password
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                            </div>
                                            </div>
                                    <div class="expandable">
                        <div title="Users" id="toc-item-users"
                             class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-4 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none">
                            <a href="#users"
                               class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0">Users</a>
                                                            <div class="sl-flex sl-items-center sl-text-xs expansion-chevrons">
                                    <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                         data-icon="chevron-right"
                                         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
                                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                        <path fill="currentColor"
                                              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
                                    </svg>
                                </div>
                                                    </div>

                                                    <div class="children" style="display: none;">
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-users-GETapi-v1-users-me">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Get Authenticated User">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#users-GETapi-v1-users-me">
                                                    Get Authenticated User
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-users-DELETEapi-v1-users-me">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Delete User">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#users-DELETEapi-v1-users-me">
                                                    Delete User
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                            </div>
                                            </div>
                                    <div class="expandable">
                        <div title="Workspaces" id="toc-item-workspaces"
                             class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-4 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none">
                            <a href="#workspaces"
                               class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0">Workspaces</a>
                                                            <div class="sl-flex sl-items-center sl-text-xs expansion-chevrons">
                                    <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                         data-icon="chevron-right"
                                         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
                                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                        <path fill="currentColor"
                                              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
                                    </svg>
                                </div>
                                                    </div>

                                                    <div class="children" style="display: none;">
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-workspaces-GETapi-v1-users-me-workspaces">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="List authenticated user workspaces">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#workspaces-GETapi-v1-users-me-workspaces">
                                                    List authenticated user workspaces
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-workspaces-POSTapi-v1-workspaces">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="Create workspace">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#workspaces-POSTapi-v1-workspaces">
                                                    Create workspace
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                            </div>
                                            </div>
                            </div>

        </div>
        <div class="sl-flex sl-items-center sl-px-4 sl-py-3 sl-border-t">
            Last updated: May 29, 2025
        </div>

        <div class="sl-flex sl-items-center sl-px-4 sl-py-3 sl-border-t">
            <a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a>
        </div>
    </div>
</div>

    <div class="sl-overflow-y-auto sl-flex-1 sl-w-full sl-px-16 sl-bg-canvas sl-py-16" style="max-width: 1500px;">

        <div class="sl-mb-10">
            <div class="sl-mb-4">
                <h1 class="sl-text-5xl sl-leading-tight sl-font-prose sl-font-semibold sl-text-heading">
                    TaskFlow API Documentation
                </h1>
                                    <a title="Download Postman collection" class="sl-mx-1"
                       href="{{ route("scribe.postman") }}" target="_blank">
                        <small>Postman collection →</small>
                    </a>
                                                    <a title="Download OpenAPI spec" class="sl-mx-1"
                       href="{{ route("scribe.openapi") }}" target="_blank">
                        <small>OpenAPI spec →</small>
                    </a>
                            </div>

            <div class="sl-prose sl-markdown-viewer sl-my-4">
                <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

                <h1 id="authenticating-requests">Authenticating requests</h1>
<p>To authenticate requests, include an <strong><code>Authorization</code></strong> header with the value <strong><code>"Bearer {YOUR_AUTH_KEY}"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>You can retrieve your token by visiting your dashboard and clicking <b>Generate API token</b>.</p>
            </div>
        </div>

        <h1 id="authentication"
        class="sl-text-5xl sl-leading-tight sl-font-prose sl-text-heading"
    >
        Authentication
    </h1>

    

                                <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="authentication-POSTapi-v1-sign-up">
                    Sign Up
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/sign-up"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: black;"
                        >
                            POST
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/sign-up</div>
                    </div>

                                    </div>
        </div>

        <p>Register a new user</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Body Parameters</h3>

                                <div class="sl-text-sm">
                                    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">name</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The name of the user.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        john doe
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">email</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The email address of the user. Must be unique. Must be a valid email address.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        johndoe@gmail.com
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">password</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>A secure password for the user. Must be at least 8 characters long. Must be at least 8 characters.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        password
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
                            </div>
                        </div>
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="POST"
              data-path="api/v1/sign-up"
              data-hasfiles="0"
              data-hasjsonbody="1">
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-sign-up-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-POSTapi-v1-sign-up-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-sign-up-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-POSTapi-v1-sign-up-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Body
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                                                    <div class="TextRequestBody sl-p-4">
                                <div class="code-editor language-json"
                                     id="json-body-POSTapi-v1-sign-up"
                                     style="font-family: var(--font-code); font-size: 12px; line-height: var(--lh-code);"
                                >{
    "name": "john doe",
    "email": "johndoe@gmail.com",
    "password": "password"
}</div>
                            </div>
                                            </div>
                </div>
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="POSTapi-v1-sign-up"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="POSTapi-v1-sign-up"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="POSTapi-v1-sign-up"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/sign-up" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"john doe\",
    \"email\": \"johndoe@gmail.com\",
    \"password\": \"password\"
}"
</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/sign-up"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "john doe",
    "email": "johndoe@gmail.com",
    "password": "password"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-POSTapi-v1-sign-up-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('POSTapi-v1-sign-up', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-sign-up example-response-POSTapi-v1-sign-up-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;User created successfully.&quot;,
    &quot;status&quot;: 200
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="authentication-POSTapi-v1-sign-in">
                    Sign In
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/sign-in"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: black;"
                        >
                            POST
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/sign-in</div>
                    </div>

                                    </div>
        </div>

        <p>Sign in a user</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Body Parameters</h3>

                                <div class="sl-text-sm">
                                    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">email</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The email address of the user attempting to sign in. Must be a valid email address.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        johndoe@gmail.com
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">password</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The password associated with the user account. Must be at least 8 characters.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        password
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
                            </div>
                        </div>
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="POST"
              data-path="api/v1/sign-in"
              data-hasfiles="0"
              data-hasjsonbody="1">
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-sign-in-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-POSTapi-v1-sign-in-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-sign-in-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-POSTapi-v1-sign-in-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Body
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                                                    <div class="TextRequestBody sl-p-4">
                                <div class="code-editor language-json"
                                     id="json-body-POSTapi-v1-sign-in"
                                     style="font-family: var(--font-code); font-size: 12px; line-height: var(--lh-code);"
                                >{
    "email": "johndoe@gmail.com",
    "password": "password"
}</div>
                            </div>
                                            </div>
                </div>
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="POSTapi-v1-sign-in"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="POSTapi-v1-sign-in"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="POSTapi-v1-sign-in"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/sign-in" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"johndoe@gmail.com\",
    \"password\": \"password\"
}"
</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/sign-in"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "johndoe@gmail.com",
    "password": "password"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-POSTapi-v1-sign-in-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('POSTapi-v1-sign-in', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">401, Invalid credentials</option>
                                                                                                            <option value="2">404, User not found</option>
                                                                                                            <option value="3">429, Rate limit exceeded</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-sign-in example-response-POSTapi-v1-sign-in-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Authenticated.&quot;,
    &quot;data&quot;: {
        &quot;token&quot;: &quot;{YOUR_AUTH_KEY}&quot;,
        &quot;id&quot;: &quot;01js7h2q08v42j8wfzng4emkvf&quot;
    },
    &quot;status&quot;: 200
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-sign-in example-response-POSTapi-v1-sign-in-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Invalid credentials.&quot;,
    &quot;status&quot;: 401
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-sign-in example-response-POSTapi-v1-sign-in-2"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;User not found with the provided credentials.&quot;,
    &quot;status&quot;: 401
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-sign-in example-response-POSTapi-v1-sign-in-3"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Too many login attempts.&quot;,
    &quot;status&quot;: 429
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="authentication-POSTapi-v1-sign-out">
                    Sign Out
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/sign-out"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: black;"
                        >
                            POST
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/sign-out</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                    </div>
        </div>

        <p>Signs out a user and deletes the auth token</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Authorization</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Bearer {YOUR_AUTH_KEY}
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="POST"
              data-path="api/v1/sign-out"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-POSTapi-v1-sign-out">Authorization</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code>Bearer </code>
                                    <input aria-label="Authorization"
                                           id="auth-POSTapi-v1-sign-out"
                                           data-component="header"
                                           data-prefix="Bearer "
                                           name="Authorization"
                                           placeholder="{YOUR_AUTH_KEY}"
                                           class="auth-value sl-relative sl-w-3/5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-sign-out-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-POSTapi-v1-sign-out-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-sign-out-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-POSTapi-v1-sign-out-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="POSTapi-v1-sign-out"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="POSTapi-v1-sign-out"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="POSTapi-v1-sign-out"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/sign-out" \
    --header "Authorization: Bearer {YOUR_AUTH_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/sign-out"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-POSTapi-v1-sign-out-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('POSTapi-v1-sign-out', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">401, Unauthenticated</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-sign-out example-response-POSTapi-v1-sign-out-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Signed out successfully.&quot;,
    &quot;status&quot;: 200
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-sign-out example-response-POSTapi-v1-sign-out-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Unauthenticated&quot;
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="authentication-POSTapi-v1-change-password">
                    Change Password
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/change-password"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: black;"
                        >
                            POST
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/change-password</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                    </div>
        </div>

        <p>Changes the password of the authenticated user</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Authorization</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Bearer {YOUR_AUTH_KEY}
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Body Parameters</h3>

                                <div class="sl-text-sm">
                                    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">current_password</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The current password of the user.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        password
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">new_password</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The new password of the user. Must be at least 8 characters.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        new_password
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
                            </div>
                        </div>
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="POST"
              data-path="api/v1/change-password"
              data-hasfiles="0"
              data-hasjsonbody="1">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-POSTapi-v1-change-password">Authorization</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code>Bearer </code>
                                    <input aria-label="Authorization"
                                           id="auth-POSTapi-v1-change-password"
                                           data-component="header"
                                           data-prefix="Bearer "
                                           name="Authorization"
                                           placeholder="{YOUR_AUTH_KEY}"
                                           class="auth-value sl-relative sl-w-3/5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-change-password-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-POSTapi-v1-change-password-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-change-password-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-POSTapi-v1-change-password-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Body
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                                                    <div class="TextRequestBody sl-p-4">
                                <div class="code-editor language-json"
                                     id="json-body-POSTapi-v1-change-password"
                                     style="font-family: var(--font-code); font-size: 12px; line-height: var(--lh-code);"
                                >{
    "current_password": "password",
    "new_password": "new_password"
}</div>
                            </div>
                                            </div>
                </div>
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="POSTapi-v1-change-password"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="POSTapi-v1-change-password"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="POSTapi-v1-change-password"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/change-password" \
    --header "Authorization: Bearer {YOUR_AUTH_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"current_password\": \"password\",
    \"new_password\": \"new_password\"
}"
</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/change-password"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "current_password": "password",
    "new_password": "new_password"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-POSTapi-v1-change-password-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('POSTapi-v1-change-password', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">400, Invalid current password</option>
                                                                                                            <option value="2">401, Unauthenticated</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-change-password example-response-POSTapi-v1-change-password-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Password updated successfully&quot;,
    &quot;status&quot;: 200
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-change-password example-response-POSTapi-v1-change-password-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Invalid current password&quot;,
    &quot;status&quot;: 400
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-change-password example-response-POSTapi-v1-change-password-2"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Unauthenticated&quot;
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="authentication-GETapi-v1-email-verify--id---hash-">
                    Verify Email
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/email/verify/{id}/{hash}"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: green;"
                        >
                            GET
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/email/verify/{id}/{hash}</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                    </div>
        </div>

        <p>Verifies the email of the authenticated user</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Authorization</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Bearer {YOUR_AUTH_KEY}
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">URL Parameters</h3>

                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">id</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The ID of the user.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        01js7h2q08v42j8wfzng4emkvf
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">hash</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The hash of the email.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        1234567890abcdef1234567890abcdef12345678
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    

                                                <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                                <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Query Parameters</h3>

                                <div class="sl-text-sm">
                                                                            <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">expires</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The expiration time of the link in seconds.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        1746693113
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                            <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">signature</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The signature of the link.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        1234567890abcdef
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                </div>
                        </div>
                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="GET"
              data-path="api/v1/email/verify/{id}/{hash}"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-GETapi-v1-email-verify--id---hash-">Authorization</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code>Bearer </code>
                                    <input aria-label="Authorization"
                                           id="auth-GETapi-v1-email-verify--id---hash-"
                                           data-component="header"
                                           data-prefix="Bearer "
                                           name="Authorization"
                                           placeholder="{YOUR_AUTH_KEY}"
                                           class="auth-value sl-relative sl-w-3/5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-email-verify--id---hash--Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-GETapi-v1-email-verify--id---hash--Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-email-verify--id---hash--Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-GETapi-v1-email-verify--id---hash--Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            URL Parameters
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                            <label aria-hidden="true"
                                       for="urlparam-GETapi-v1-email-verify--id---hash--id">id</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="id" name="id"
                                               id="urlparam-GETapi-v1-email-verify--id---hash--id"
                                               placeholder="The ID of the user."
                                               value="01js7h2q08v42j8wfzng4emkvf" data-component="url"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                            <label aria-hidden="true"
                                       for="urlparam-GETapi-v1-email-verify--id---hash--hash">hash</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="hash" name="hash"
                                               id="urlparam-GETapi-v1-email-verify--id---hash--hash"
                                               placeholder="The hash of the email."
                                               value="1234567890abcdef1234567890abcdef12345678" data-component="url"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Query Parameters
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="queryparam-GETapi-v1-email-verify--id---hash--expires">expires</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                                                                    <input aria-label="expires" name="expires"
                                                   id="queryparam-GETapi-v1-email-verify--id---hash--expires"
                                                   placeholder="The expiration time of the link in seconds."
                                                   value="1746693113" data-component="query"
                                                   class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border"
                                            >
                                                                            </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="queryparam-GETapi-v1-email-verify--id---hash--signature">signature</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                                                                    <input aria-label="signature" name="signature"
                                                   id="queryparam-GETapi-v1-email-verify--id---hash--signature"
                                                   placeholder="The signature of the link."
                                                   value="1234567890abcdef" data-component="query"
                                                   class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border"
                                            >
                                                                            </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="GETapi-v1-email-verify--id---hash-"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="GETapi-v1-email-verify--id---hash-"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="GETapi-v1-email-verify--id---hash-"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/email/verify/01js7h2q08v42j8wfzng4emkvf/1234567890abcdef1234567890abcdef12345678?expires=1746693113&amp;signature=1234567890abcdef" \
    --header "Authorization: Bearer {YOUR_AUTH_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/email/verify/01js7h2q08v42j8wfzng4emkvf/1234567890abcdef1234567890abcdef12345678"
);

const params = {
    "expires": "1746693113",
    "signature": "1234567890abcdef",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-GETapi-v1-email-verify--id---hash--toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('GETapi-v1-email-verify--id---hash-', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">401, Unauthenticated</option>
                                                                                                            <option value="2">403, Invalid url</option>
                                                                                                            <option value="3">403, Email already verified</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-email-verify--id---hash- example-response-GETapi-v1-email-verify--id---hash--0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Email verified successfully&quot;,
    &quot;status&quot;: 200
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-email-verify--id---hash- example-response-GETapi-v1-email-verify--id---hash--1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Unauthenticated&quot;
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-email-verify--id---hash- example-response-GETapi-v1-email-verify--id---hash--2"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Invalid url&quot;,
    &quot;status&quot;: 403
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-email-verify--id---hash- example-response-GETapi-v1-email-verify--id---hash--3"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Email already verified&quot;,
    &quot;status&quot;: 403
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="authentication-POSTapi-v1-email-verification-notification">
                    Resend Email Verification
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/email/verification-notification"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: black;"
                        >
                            POST
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/email/verification-notification</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                    </div>
        </div>

        <p>Resends the email verification notification to the authenticated user</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Authorization</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Bearer {YOUR_AUTH_KEY}
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="POST"
              data-path="api/v1/email/verification-notification"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-POSTapi-v1-email-verification-notification">Authorization</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code>Bearer </code>
                                    <input aria-label="Authorization"
                                           id="auth-POSTapi-v1-email-verification-notification"
                                           data-component="header"
                                           data-prefix="Bearer "
                                           name="Authorization"
                                           placeholder="{YOUR_AUTH_KEY}"
                                           class="auth-value sl-relative sl-w-3/5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-email-verification-notification-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-POSTapi-v1-email-verification-notification-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-email-verification-notification-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-POSTapi-v1-email-verification-notification-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="POSTapi-v1-email-verification-notification"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="POSTapi-v1-email-verification-notification"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="POSTapi-v1-email-verification-notification"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/email/verification-notification" \
    --header "Authorization: Bearer {YOUR_AUTH_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/email/verification-notification"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-POSTapi-v1-email-verification-notification-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('POSTapi-v1-email-verification-notification', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">401, Unauthenticated</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-email-verification-notification example-response-POSTapi-v1-email-verification-notification-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Verification link sent!&quot;,
    &quot;status&quot;: 200
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-email-verification-notification example-response-POSTapi-v1-email-verification-notification-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Unauthenticated&quot;
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="authentication-POSTapi-v1-forgot-password">
                    Send Password Reset Link
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/forgot-password"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: black;"
                        >
                            POST
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/forgot-password</div>
                    </div>

                                    </div>
        </div>

        <p>Sends a password reset link to the user's email</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Body Parameters</h3>

                                <div class="sl-text-sm">
                                    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">email</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The email address of the user requesting the password reset link. Must be a valid email address. The <code>email</code> of an existing record in the users table.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        johndoe@gmail.com
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
                            </div>
                        </div>
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="POST"
              data-path="api/v1/forgot-password"
              data-hasfiles="0"
              data-hasjsonbody="1">
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-forgot-password-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-POSTapi-v1-forgot-password-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-forgot-password-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-POSTapi-v1-forgot-password-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Body
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                                                    <div class="TextRequestBody sl-p-4">
                                <div class="code-editor language-json"
                                     id="json-body-POSTapi-v1-forgot-password"
                                     style="font-family: var(--font-code); font-size: 12px; line-height: var(--lh-code);"
                                >{
    "email": "johndoe@gmail.com"
}</div>
                            </div>
                                            </div>
                </div>
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="POSTapi-v1-forgot-password"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="POSTapi-v1-forgot-password"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="POSTapi-v1-forgot-password"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/forgot-password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"johndoe@gmail.com\"
}"
</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/forgot-password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "johndoe@gmail.com"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-POSTapi-v1-forgot-password-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('POSTapi-v1-forgot-password', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">400, Failure</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-forgot-password example-response-POSTapi-v1-forgot-password-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Reset link sent successfully&quot;,
    &quot;status&quot;: 200
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-forgot-password example-response-POSTapi-v1-forgot-password-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Failed to send reset link&quot;
    &quot;status&quot;: 400
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="authentication-POSTapi-v1-reset-password">
                    Reset Password
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/reset-password"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: black;"
                        >
                            POST
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/reset-password</div>
                    </div>

                                    </div>
        </div>

        <p>Resets the password of the user</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Body Parameters</h3>

                                <div class="sl-text-sm">
                                    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">token</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The password reset token.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">email</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The email address of the user. Must be a valid email address. The <code>email</code> of an existing record in the users table.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        johndoe@gmail.com
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">password</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The new password for the user. Must be at least 8 characters.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        newpassword123
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
                            </div>
                        </div>
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="POST"
              data-path="api/v1/reset-password"
              data-hasfiles="0"
              data-hasjsonbody="1">
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-reset-password-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-POSTapi-v1-reset-password-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-reset-password-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-POSTapi-v1-reset-password-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Body
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                                                    <div class="TextRequestBody sl-p-4">
                                <div class="code-editor language-json"
                                     id="json-body-POSTapi-v1-reset-password"
                                     style="font-family: var(--font-code); font-size: 12px; line-height: var(--lh-code);"
                                >{
    "token": "1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef",
    "email": "johndoe@gmail.com",
    "password": "newpassword123"
}</div>
                            </div>
                                            </div>
                </div>
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="POSTapi-v1-reset-password"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="POSTapi-v1-reset-password"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="POSTapi-v1-reset-password"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/reset-password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"token\": \"1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef\",
    \"email\": \"johndoe@gmail.com\",
    \"password\": \"newpassword123\"
}"
</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/reset-password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "token": "1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef",
    "email": "johndoe@gmail.com",
    "password": "newpassword123"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-POSTapi-v1-reset-password-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('POSTapi-v1-reset-password', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">400, Failure</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-reset-password example-response-POSTapi-v1-reset-password-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Password reset successfully&quot;,
    &quot;status&quot;: 200
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-reset-password example-response-POSTapi-v1-reset-password-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Failed to reset password&quot;
    &quot;status&quot;: 400
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                <h1 id="users"
        class="sl-text-5xl sl-leading-tight sl-font-prose sl-text-heading"
    >
        Users
    </h1>

    

                                <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="users-GETapi-v1-users-me">
                    Get Authenticated User
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/users/me"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: green;"
                        >
                            GET
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/users/me</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                    </div>
        </div>

        <p>Retrieve the currently authenticated user</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Authorization</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Bearer {YOUR_AUTH_KEY}
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="GET"
              data-path="api/v1/users/me"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-GETapi-v1-users-me">Authorization</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code>Bearer </code>
                                    <input aria-label="Authorization"
                                           id="auth-GETapi-v1-users-me"
                                           data-component="header"
                                           data-prefix="Bearer "
                                           name="Authorization"
                                           placeholder="{YOUR_AUTH_KEY}"
                                           class="auth-value sl-relative sl-w-3/5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-users-me-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-GETapi-v1-users-me-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-users-me-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-GETapi-v1-users-me-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="GETapi-v1-users-me"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="GETapi-v1-users-me"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="GETapi-v1-users-me"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/users/me" \
    --header "Authorization: Bearer {YOUR_AUTH_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/users/me"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-GETapi-v1-users-me-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('GETapi-v1-users-me', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">401, Unauthenticated</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-users-me example-response-GETapi-v1-users-me-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01971c0c-f29c-726b-9676-da729f9697a1&quot;,
        &quot;name&quot;: &quot;Morgan Hirthe&quot;,
        &quot;email&quot;: &quot;dare.emelie@example.com&quot;,
        &quot;email_verified_at&quot;: &quot;2025-05-29T12:38:19.000000Z&quot;,
        &quot;created_at&quot;: &quot;2025-05-29T12:38:20.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-05-29T12:38:20.000000Z&quot;
    }
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-users-me example-response-GETapi-v1-users-me-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Unauthenticated&quot;
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="users-DELETEapi-v1-users-me">
                    Delete User
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/users/me"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: red;"
                        >
                            DELETE
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/users/me</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                    </div>
        </div>

        <p>Deletes the authenticated user</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Authorization</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Bearer {YOUR_AUTH_KEY}
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Body Parameters</h3>

                                <div class="sl-text-sm">
                                    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">password</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The password of the user attempting to delete their account. Must be at least 8 characters.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        password
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
                            </div>
                        </div>
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="DELETE"
              data-path="api/v1/users/me"
              data-hasfiles="0"
              data-hasjsonbody="1">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-DELETEapi-v1-users-me">Authorization</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code>Bearer </code>
                                    <input aria-label="Authorization"
                                           id="auth-DELETEapi-v1-users-me"
                                           data-component="header"
                                           data-prefix="Bearer "
                                           name="Authorization"
                                           placeholder="{YOUR_AUTH_KEY}"
                                           class="auth-value sl-relative sl-w-3/5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                                                            <label aria-hidden="true"
                                       for="header-DELETEapi-v1-users-me-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-DELETEapi-v1-users-me-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-DELETEapi-v1-users-me-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-DELETEapi-v1-users-me-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Body
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                                                    <div class="TextRequestBody sl-p-4">
                                <div class="code-editor language-json"
                                     id="json-body-DELETEapi-v1-users-me"
                                     style="font-family: var(--font-code); font-size: 12px; line-height: var(--lh-code);"
                                >{
    "password": "password"
}</div>
                            </div>
                                            </div>
                </div>
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="DELETEapi-v1-users-me"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="DELETEapi-v1-users-me"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="DELETEapi-v1-users-me"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/users/me" \
    --header "Authorization: Bearer {YOUR_AUTH_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"password\": \"password\"
}"
</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/users/me"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "password": "password"
};

fetch(url, {
    method: "DELETE",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-DELETEapi-v1-users-me-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('DELETEapi-v1-users-me', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">400, Incorrect password</option>
                                                                                                            <option value="2">401, Unauthenticated</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-DELETEapi-v1-users-me example-response-DELETEapi-v1-users-me-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;User deleted successfully&quot;,
    &quot;status&quot;: 200
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-DELETEapi-v1-users-me example-response-DELETEapi-v1-users-me-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Incorrect password.&quot;,
    &quot;status&quot;: 400
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-DELETEapi-v1-users-me example-response-DELETEapi-v1-users-me-2"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;message&quot;: &quot;Unauthenticated&quot;
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                <h1 id="workspaces"
        class="sl-text-5xl sl-leading-tight sl-font-prose sl-text-heading"
    >
        Workspaces
    </h1>

    

                                <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="workspaces-GETapi-v1-users-me-workspaces">
                    List authenticated user workspaces
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/users/me/workspaces"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: green;"
                        >
                            GET
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/users/me/workspaces</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                    </div>
        </div>

        <p>List all workspaces for the authenticated user.</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Authorization</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Bearer {YOUR_AUTH_KEY}
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="GET"
              data-path="api/v1/users/me/workspaces"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-GETapi-v1-users-me-workspaces">Authorization</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code>Bearer </code>
                                    <input aria-label="Authorization"
                                           id="auth-GETapi-v1-users-me-workspaces"
                                           data-component="header"
                                           data-prefix="Bearer "
                                           name="Authorization"
                                           placeholder="{YOUR_AUTH_KEY}"
                                           class="auth-value sl-relative sl-w-3/5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-users-me-workspaces-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-GETapi-v1-users-me-workspaces-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-users-me-workspaces-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-GETapi-v1-users-me-workspaces-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="GETapi-v1-users-me-workspaces"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="GETapi-v1-users-me-workspaces"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="GETapi-v1-users-me-workspaces"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/users/me/workspaces" \
    --header "Authorization: Bearer {YOUR_AUTH_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/users/me/workspaces"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-GETapi-v1-users-me-workspaces-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('GETapi-v1-users-me-workspaces', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">401, Unauthenticated</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-users-me-workspaces example-response-GETapi-v1-users-me-workspaces-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;01971c0c-f2ba-7384-9d7d-90913fe38a9c&quot;,
            &quot;name&quot;: &quot;Price Ltd&quot;,
            &quot;imagePath&quot;: &quot;https://via.placeholder.com/640x480.png/00cc99?text=business+Workspace+nostrum&quot;,
            &quot;user_id&quot;: &quot;01971c0c-f2b7-73a5-9f0a-fab5362b4316&quot;,
            &quot;created_at&quot;: &quot;2025-05-29T12:38:20.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-05-29T12:38:20.000000Z&quot;
        },
        {
            &quot;id&quot;: &quot;01971c0c-f2c3-7182-8e74-3352c9cb0b2b&quot;,
            &quot;name&quot;: &quot;McLaughlin and Sons&quot;,
            &quot;imagePath&quot;: &quot;https://via.placeholder.com/640x480.png/0088bb?text=business+Workspace+aut&quot;,
            &quot;user_id&quot;: &quot;01971c0c-f2c1-7393-a0b7-7c91a1a257b5&quot;,
            &quot;created_at&quot;: &quot;2025-05-29T12:38:20.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-05-29T12:38:20.000000Z&quot;
        }
    ]
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-users-me-workspaces example-response-GETapi-v1-users-me-workspaces-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
      &quot;message&quot;: &quot;Unauthenticated&quot;,
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="workspaces-POSTapi-v1-workspaces">
                    Create workspace
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8000/api/v1/workspaces"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: black;"
                        >
                            POST
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8000</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/workspaces</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                    </div>
        </div>

        <p>Create a new workspace for the authenticated user.</p>
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Authorization</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Bearer {YOUR_AUTH_KEY}
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        multipart/form-data
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Body Parameters</h3>

                                <div class="sl-text-sm">
                                    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">name</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The name of the workspace. Must not be greater than 50 characters.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        My Workspace
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">image</div>
                                            <span class="sl-truncate sl-text-muted">file</span>
                                    </div>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The image for the workspace. Must be an image. Must not be greater than 4096 kilobytes.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        /tmp/phpp8tmbup2jpaheoHpkHA
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
                            </div>
                        </div>
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="POST"
              data-path="api/v1/workspaces"
              data-hasfiles="1"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-POSTapi-v1-workspaces">Authorization</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code>Bearer </code>
                                    <input aria-label="Authorization"
                                           id="auth-POSTapi-v1-workspaces"
                                           data-component="header"
                                           data-prefix="Bearer "
                                           name="Authorization"
                                           placeholder="{YOUR_AUTH_KEY}"
                                           class="auth-value sl-relative sl-w-3/5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-workspaces-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-POSTapi-v1-workspaces-Content-Type"
                                               value="multipart/form-data" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-workspaces-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-POSTapi-v1-workspaces-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Body
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                                                    <div class="ParameterGrid sl-p-4">
                                                                                                        <label aria-hidden="true"
                                           for="bodyparam-POSTapi-v1-workspaces-name">name</label>
                                    <span class="sl-mx-3">:</span>
                                    <div class="sl-flex sl-flex-1">
                                        <div class="sl-input sl-flex-1 sl-relative">
                                                                                            <input aria-label="name" name="name"
                                                       id="bodyparam-POSTapi-v1-workspaces-name"
                                                       placeholder="The name of the workspace. Must not be greater than 50 characters."
                                                       value="My Workspace" data-component="body"
                                                       class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border"
                                                >
                                                                                    </div>
                                    </div>
                                                                                                        <label aria-hidden="true"
                                           for="bodyparam-POSTapi-v1-workspaces-image">image</label>
                                    <span class="sl-mx-3">:</span>
                                    <div class="sl-flex sl-flex-1">
                                        <div class="sl-input sl-flex-1 sl-relative">
                                                                                            <input aria-label="image" name="image"
                                                       id="bodyparam-POSTapi-v1-workspaces-image"
                                                       type="file" data-component="body"
                                                       class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border"
                                                >
                                                                                    </div>
                                    </div>
                                                            </div>
                                            </div>
                </div>
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="POSTapi-v1-workspaces"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="POSTapi-v1-workspaces"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="POSTapi-v1-workspaces"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/workspaces" \
    --header "Authorization: Bearer {YOUR_AUTH_KEY}" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "name=My Workspace"\
    --form "image=@/tmp/phpp8tmbup2jpaheoHpkHA" </code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/workspaces"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_KEY}",
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('name', 'My Workspace');
body.append('image', document.querySelector('input[name="image"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-POSTapi-v1-workspaces-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('POSTapi-v1-workspaces', event.target.value);">
                                                                                                            <option value="0">200, Success</option>
                                                                                                            <option value="1">401, Unauthenticated</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-workspaces example-response-POSTapi-v1-workspaces-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;01971c0c-f2da-716d-a833-d7377d435187&quot;,
        &quot;name&quot;: &quot;Bailey Inc&quot;,
        &quot;imagePath&quot;: &quot;https://via.placeholder.com/640x480.png/00cccc?text=business+Workspace+animi&quot;,
        &quot;user_id&quot;: &quot;01971c0c-f2d9-73ab-a3a5-52f79789e8ea&quot;,
        &quot;created_at&quot;: &quot;2025-05-29T12:38:20.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-05-29T12:38:20.000000Z&quot;
    }
}</code></pre>
                                                                            </div>
                                </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-POSTapi-v1-workspaces example-response-POSTapi-v1-workspaces-1"
                                     style=" display: none;"
                                >
                                    <div class="sl-panel__content sl-p-0">                                                                                                                                
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
      &quot;message&quot;: &quot;Unauthenticated&quot;,
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

            

        <div class="sl-prose sl-markdown-viewer sl-my-5">
            
        </div>
    </div>

</div>

<template id="expand-chevron">
    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="chevron-right"
         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
        <path fill="currentColor"
              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
    </svg>
</template>

<template id="expanded-chevron">
    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="chevron-down"
         class="svg-inline--fa fa-chevron-down fa-fw sl-icon sl-text-muted"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
        <path fill="currentColor"
              d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"></path>
    </svg>
</template>

<template id="expand-chevron-solid">
    <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="caret-right"
         class="svg-inline--fa fa-caret-right fa-fw sl-icon" role="img" xmlns="http://www.w3.org/2000/svg"
         viewBox="0 0 256 512">
        <path fill="currentColor"
              d="M118.6 105.4l128 127.1C252.9 239.6 256 247.8 256 255.1s-3.125 16.38-9.375 22.63l-128 127.1c-9.156 9.156-22.91 11.9-34.88 6.943S64 396.9 64 383.1V128c0-12.94 7.781-24.62 19.75-29.58S109.5 96.23 118.6 105.4z"></path>
    </svg>
</template>

<template id="expanded-chevron-solid">
    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="caret-down"
         class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
        <path fill="currentColor"
              d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
    </svg>
</template>
</body>
</html>
