import { useEffect, useState } from 'react';

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
  onGiftbackApplyEvent: (event: any) => void;
  onGiftbackInactiveEvent: (event: any) => void;
  onGiftbackCompleteEvent: (event: any) => void;
  value: number;
  description?: string;
  customer?: Customer;
  appID: string;
  correlationID: string;
  retry: string;
};
const Checkout = ({
  onSuccess,
  onGiftbackApplyEvent,
  onGiftbackInactiveEvent,
  onGiftbackCompleteEvent,
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
    const shouldRetry = (retry !== oldRetry || !giftbackCalled) && isOpenPixLoaded;

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

        if (e.type === 'PAYMENT_STATUS') {
          if (e.data.status === 'COMPLETED') {
            // setCorrelationID(getDefaultTransactionId());

            window.$openpix.push(['close']);
            onSuccess && onSuccess(correlationID);
          }
        }
      };

      const unsubscribe = window.$openpix.addEventListener(logEvents);
      const giftbackUnsubscribe = window.$openpix.addEventListener(onGiftbackApplyEvent);
      const giftbackInactiveUnsubscribe = window.$openpix.addEventListener(onGiftbackInactiveEvent);
      const giftbackCompleteUnsubscribe = window.$openpix.addEventListener(onGiftbackCompleteEvent);

      return () => {
        unsubscribe && unsubscribe();
        giftbackUnsubscribe && giftbackUnsubscribe();
        giftbackInactiveUnsubscribe && giftbackInactiveUnsubscribe();
        giftbackCompleteUnsubscribe && giftbackCompleteUnsubscribe();
      };
    }
  }, [isOpenPixLoaded]);

  return null;
};

export default Checkout;
