import HijackClick from "./HijackClick";
import {render} from "react-dom";
import {getHostNode} from "./getHostNode";
import { StrictMode } from 'react';

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
  )
}