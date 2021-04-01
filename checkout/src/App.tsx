import { useEffect, useState } from 'react';

import { v4 as uuidv4 } from 'uuid';

import { useOpenPix } from './useOpenPix';

export const getDefaultTransactionId = () =>
  uuidv4().toString().replace(/-/g, '');

type Customer = {
  name: string;
  email: string;
  taxID: string;
  phone: string;
};
export type AppProps = {
  onSuccess: (correlationID: string) => void;
  value: number;
  description?: string;
  customer?: Customer;
  appID: string;
};
const App = ({ onSuccess, value, description, customer, appID }: AppProps) => {
  // generate a new transactionID on mount
  // eslint-disable-next-line
  const [correlationID, setCorrelationID] = useState(() =>
    getDefaultTransactionId(),
  );

  useOpenPix(appID);

  const isOpenPixLoaded = !!window.$openpix?.addEventListener;

  useEffect(() => {
    if (isOpenPixLoaded) {
      window.$openpix.push([
        'pix',
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
  }, [isOpenPixLoaded]);

  return null;
};

export default App;
