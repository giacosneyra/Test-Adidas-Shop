import React, { useState, useEffect } from 'react';

export const WcNoticeBlock = ({ type, message, link = undefined }) => {
    return (
        <div className={ "wc-block-components-notices" }>
            <div className={ `wc-block-store-notice wc-block-components-notice-banner ${ type }` }>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path></svg>
                <div className={ "wc-block-components-notice-banner__content" }>
                    <div>{ message ?? 'Oops! Something went wrong' } { link &&
                        <a href={ link.url } target="_blank" rel="noopener noreferrer">{ link.text }</a>
                    }</div>
                </div>
            </div>
        </div>
    );
}
