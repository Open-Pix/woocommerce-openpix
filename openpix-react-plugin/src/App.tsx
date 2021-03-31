import { useEffect, useState } from 'react';

import styled from 'styled-components';
import { Flex } from 'rebass';
import { v4 as uuidv4 } from 'uuid';
import { space } from 'styled-system';
import ShoppingCartIcon from '@material-ui/icons/ShoppingCart';

import { useOpenPix } from './useOpenPix';

const Button = styled.button`
  ${space}
`;

export const getDefaultTransactionId = () =>
  uuidv4().toString().replace(/-/g, '');

type Customer = {
  name: string,
  email: string,
  taxID: string,
  phone: string,
}
export type AppProps = {
  onSuccess: (correlationID: string) => void;
  value: number,
  description?: string,
  customer?: Customer,
};
const App = ({
               onSuccess,
  value,
  description,
  customer,
}: AppProps) => {
  // generate a new transactionID on mount
  const [correlationID, setCorrelationID] = useState(() =>
    getDefaultTransactionId(),
  );

  useOpenPix();

  const onClick = () => {
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
  };

  const isOpenPixLoaded = !!window.$openpix?.addEventListener;

  useEffect(() => {
    if (isOpenPixLoaded) {
      window.$openpix.push([
        'pix',
        {
          value: 1,
          correlationID,
          description: 'OpenPix Demo',
        },
      ]);

      const logEvents = (e) => {
        // eslint-disable-next-line
        console.log('logEvents: ', e);

        if (e.type === 'PAYMENT_STATUS') {
          if (e.data.status === 'COMPLETED') {
            // setCorrelationID(getDefaultTransactionId());

            window.$openpix.push([
              'close',
            ]);
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

  return (
    <Flex
      mt="80px"
      mb="80px"
      alignItems="center"
      justifyContent="center"
      flexDirection="column"
      minHeight="250px"
    >
      <Button
        variant="outlined"
        color="primary"
        onClick={onClick}
        endIcon={<ShoppingCartIcon />}
      >
        Pay with Pix ok
      </Button>
    </Flex>
  );
};

export default App;
