## kubernetes receipt ##

```yaml
apiVersion: v1
kind: Pod
metadata:
  name: infrastructure
  labels:
    app: infrastructure
spec:
  containers:
    - name: logs-consumer
      image: my-symfony-app:latest
      command: ['bin/console', 'rabbitmq:consume', '--skip-declare', 'logs']
  initContainers:
   - name: declare-rabbitmq
     image: my-symfony-app:latest
     command: ['bin/console', 'rabbitmq:declare', 'infrastructure'] # specify connection name
```

Don't forgot create declarations for producers of your web project

```yaml
apiVersion: v1
kind: Pod
metadata:
  name: crm
  labels:
    app: crm
spec:
  initContainers:
    - name: declare-rabbitmq
      image: my-symfony-app:latest
      command: ['bin/console', 'rabbitmq:declare', 'crm'] # specify connection name
  containers:
    - name: web
      image: my-nginx:latest
    - name: crm-php-fpm
      image: my-symfony-app:latest
```