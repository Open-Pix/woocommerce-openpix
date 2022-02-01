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
  onCashbackApplyEvent: (event: any) => void;
  onCashbackInactiveEvent: (event: any) => void;
  onCashbackCompleteEvent: (event: any) => void;
  value: number;
  description?: string;
  customer?: Customer;
  appID: string;
  correlationID: string;
  retry: string;
};
const Checkout = ({
  onSuccess,
  onCashbackApplyEvent,
  onCashbackInactiveEvent,
  onCashbackCompleteEvent,
  value,
  description,
  customer,
  appID,
  correlationID,
  retry,
}: AppProps) => {
  const [cashbackCalled, setCashbackCalled] = useState<boolean>(false);

  useOpenPix(appID);

  const oldRetry = usePrevious(retry);

  const isOpenPixLoaded = !!window.$openpix?.addEventListener;

  useEffect(() => {
    const shouldRetry = (retry !== oldRetry || !cashbackCalled) && isOpenPixLoaded;

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

      if (!cashbackCalled) {
        setCashbackCalled(true);
      }
    }
  }, [isOpenPixLoaded, retry, cashbackCalled]);

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
      const cashbackUnsubscribe = window.$openpix.addEventListener(onCashbackApplyEvent);
      const cashbackInactiveUnsubscribe = window.$openpix.addEventListener(onCashbackInactiveEvent);
      const cashbackCompleteUnsubscribe = window.$openpix.addEventListener(onCashbackCompleteEvent);

      return () => {
        unsubscribe && unsubscribe();
        cashbackUnsubscribe && cashbackUnsubscribe();
        cashbackInactiveUnsubscribe && cashbackInactiveUnsubscribe();
        cashbackCompleteUnsubscribe && cashbackCompleteUnsubscribe();
      };
    }
  }, [isOpenPixLoaded]);

  return null;
};

export default Checkout;
