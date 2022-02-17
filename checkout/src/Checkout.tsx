import { useEffect, useState } from 'react';

import { v4 as uuidv4 } from 'uuid';

import { useOpenPix } from './useOpenPix';
import { usePrevious } from './usePrevious';
import EntryType from './EntryType';

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
  onEvent: (event: any) => void;
  value: number;
  description?: string;
  customer?: Customer;
  appID: string;
  correlationID: string;
  retry: string;
};
const Checkout = ({
  onSuccess,
  onEvent,
  value,
  description,
  customer,
  appID,
  correlationID,
  retry,
}: AppProps) => {
  const [giftbackCalled, setGiftbackCalled] = useState<boolean>(false);

  useOpenPix(appID);

  const oldRetry = usePrevious(retry);

  const isOpenPixLoaded = !!window.$openpix?.addEventListener;

  useEffect(() => {
    const shouldRetry =
      (retry !== oldRetry || !giftbackCalled) && isOpenPixLoaded;

    if (shouldRetry) {
      window.$openpix.push([
        'giftback',
        {
          correlationID,
          value,
          description,
          customer,
          closeOnSuccess: true,
        },
      ]);

      if (!giftbackCalled) {
        setGiftbackCalled(true);
      }
    }
  }, [isOpenPixLoaded, retry, giftbackCalled]);

  useEffect(() => {
    if (isOpenPixLoaded) {
      const logEvents = (e) => {
        // eslint-disable-next-line
        console.log('logEvents: ', e);

        if (e.type === EntryType.PAYMENT_STATUS) {
          if (e.data.status === 'COMPLETED') {
            // setCorrelationID(getDefaultTransactionId());

            window.$openpix.push(['close']);
            onSuccess && onSuccess(correlationID);
          }
        }
      };

      const unsubscribe = window.$openpix.addEventListener(logEvents);
      const event = window.$openpix.addEventListener(onEvent);

      return () => {
        unsubscribe && unsubscribe();
        event && event();
      };
    }
  }, [isOpenPixLoaded]);

  return null;
};

export default Checkout;
