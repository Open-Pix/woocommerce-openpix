export const getHostNode = (id: string) => {
  const hostNodes = document.querySelectorAll(`#${id}`);

  if (hostNodes.length !== 0) {
    return hostNodes[0];
  }

  const hostDiv = document.createElement("div");
  hostDiv.setAttribute("id", id);

  document.documentElement.appendChild(hostDiv);

  return hostDiv;
};