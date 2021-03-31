import { render } from 'react-dom';

import { StrictMode } from 'react';

import HijackClick from './HijackClick';
import { getHostNode } from './getHostNode';


export const hijackClickReact = (onClick) => {
  const host = getHostNode('openpix-checkout');

  render(
    <StrictMode>
      <HijackClick
        selector='form.checkout #place_order'
        onClick={onClick}
        event='click'
      />
    </StrictMode>,
    host,
  );
};
