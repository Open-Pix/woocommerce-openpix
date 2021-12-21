import { useEffect } from 'react';

import { v4 as uuidv4 } from 'uuid';

import { useOpenPix } from './useOpenPix';
import {usePrevious} from "./usePrevious";

export const getDefaultTransactionId = () =>
  uuidv4().toString().replace(/-/g, '');

export type Customer = {
  name: string;
  email: string;
  taxID: string;
  phone: string;
};
export type AppProps = {
  // eslint-disable-next-line
  onSuccess: (correlationID: string) => void;
  value: number;
  description?: string;
  customer?: Customer;
  appID: string;
  correlationID: string;
  retry: string;
};
const Checkout = ({
  onSuccess,
  value,
  description,
  customer,
  appID,
  correlationID,
    retry,
}: AppProps) => {
  useOpenPix(appID);

  const oldRetry = usePrevious(retry);

  const isOpenPixLoaded = !!window.$openpix?.addEventListener;

  useEffect(() => {
    const shouldRetry = retry !== oldRetry && isOpenPixLoaded;

    if (shouldRetry) {
      window.$openpix.push([
        'cashback',
        {
          correlationID,
          value,
          description,
          customer,
          closeOnSuccess: true,
        },
      ]);

      const logEvents = (e) => {
        // eslint-disable-next-line
        console.log('logEvents: ', e);

        if (e.type === 'PAYMENT_STATUS') {
          if (e.data.status === 'COMPLETED') {
            // setCorrelationID(getDefaultTransactionId());

            window.$openpix.push(['close']);
            onSuccess && onSuccess(correlationID);
          }
        }
      };

      const unsubscribe = window.$openpix.addEventListener(logEvents);

      return () => {
        unsubscribe && unsubscribe();
      };
    }
  }, [isOpenPixLoaded, retry]);

  return null;
};

export default Checkout;
