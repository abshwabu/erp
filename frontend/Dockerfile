FROM node:20-alpine

WORKDIR /opt/frontend

COPY package.json ./

RUN npm install

EXPOSE 3000

COPY docker/start-frontend.sh /usr/local/bin/start-frontend.sh

RUN chmod +x /usr/local/bin/start-frontend.sh

CMD ["/usr/local/bin/start-frontend.sh"]
