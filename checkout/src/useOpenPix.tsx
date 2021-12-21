import { useEffect } from 'react';

import { useScript } from './useScript';
import { config } from './config';

export type IOpenPixApi = {
  generateStatic: (options: any) => any;
  status: () => void;
  addEventListener: () => void;
};

declare global {
  interface Window {
    $openpix: unknown[] & IOpenPixApi;
  }
}

export const useOpenPix = (appID: string) => {
  useEffect(() => {
    window.$openpix = [];
    window.$openpix.push(['config', { appID }]);
  }, [appID]);

  const scriptURL = config.OPENPIX_PLUGIN_URL;

  console.log({
    config,
  });

  // eslint-disable-next-line
  const [loaded, error] = useScript(scriptURL);

  useEffect(() => {
    if (!error) {
      return;
    }

    // eslint-disable-next-line
    console.log('OpenPix not loaded');
  }, [error]);
};
