import {useEffect, useRef} from "react";

type HijackClickProps = {
  selector: string,
  event: string,
  onClick: () => void,
}
const HijackClick = ({ selector, event, onClick }: HijackClickProps) => {
  const elm = useRef<HTMLButtonElement>();

  useEffect(() => {
    elm.current = document.querySelector(selector);

    console.log({
      elm: elm.current,
    });

    if (!elm.current) {
      // eslint-disable-next-line
      console.log('not found');
      return;
    }

    const handleClick = (e) => {
      e.preventDefault();
      e.stopPropagation();

      onClick();
    }

    elm.current.addEventListener(event, handleClick);

    return () => {
      elm.current.removeEventListener(event, handleClick);
    }
  }, [selector, event]);

  return null;
}

export default HijackClick;