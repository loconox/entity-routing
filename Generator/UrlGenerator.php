<?php

namespace Loconox\EntityRoutingBundle\Generator;

use Loconox\EntityRoutingBundle\Host\HostServiceManager;
use Loconox\EntityRoutingBundle\Host\Service\HostServiceInterface;
use Loconox\EntityRoutingBundle\Slug\Service\SlugServiceInterface;
use Loconox\EntityRoutingBundle\Slug\SlugServiceManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Generator\UrlGenerator as BaseUrlGenerator;
use Symfony\Component\Validator\ConstraintViolation;


class UrlGenerator extends BaseUrlGenerator
{
    /**
     * @var SlugServiceManager
     */
    protected $slugServiceManager;

    /**
     * @var HostServiceManager
     */
    protected $hostServiceManager;

    /**
     * Constructor.
     *
     * @param RouteCollection $routes A RouteCollection instance
     * @param RequestContext $context The context
     * @param SlugServiceManager $slugServiceManager
     * @param HostServiceManager $hostServiceManager
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        RouteCollection $routes,
        RequestContext $context,
        SlugServiceManager $slugServiceManager,
        HostServiceManager $hostServiceManager,
        LoggerInterface $logger = null
    )
    {
        parent::__construct($routes, $context, $logger);
        $this->slugServiceManager = $slugServiceManager;
        $this->hostServiceManager = $hostServiceManager;
    }

    /**
     * {@inheritdoc}
     */
    public
    function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (null === $route = $this->routes->get($name)) {
            throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
        }

        // the Route has a cache of its own and is not recompiled as long as it does not get modified
        $compiledRoute = $route->compile();

        $variables = array_flip($compiledRoute->getVariables());
        $defaults = $route->getDefaults();
        $requirements = $route->getRequirements();
        $tokens = $compiledRoute->getTokens();
        $hostTokens = $compiledRoute->getHostTokens();
        $requiredSchemes = $route->getSchemes();
        $mergedParams = array_replace($defaults, $this->context->getParameters(), $parameters);

        // all params must be given
        if ($diff = array_diff_key($variables, $mergedParams)) {
            throw new MissingMandatoryParametersException(
                sprintf(
                    'Some mandatory parameters are missing ("%s") to generate a URL for route "%s".',
                    implode('", "', array_keys($diff)),
                    $name
                )
            );
        }

        $url = '';
        $optional = true;
        $message = 'Parameter "{parameter}" for route "{route}" must match "{expected}" ("{given}" given) to generate a corresponding URL.';

        // build url based on tokens
        foreach ($tokens as $token) {
            if ('variable' === $token[0]) {
                list ($type, $precedingChar, $regexp, $varName) = $token;

                // it's a simple variable
                if (!$optional || !array_key_exists(
                        $varName,
                        $defaults
                    ) || null !== $mergedParams[$varName] && (string)$mergedParams[$varName] !== (string)$defaults[$varName]
                ) {
                    /** @var SlugServiceInterface $slugService */
                    $slugService = $this->slugServiceManager->get($varName, $route);
                    // It's a entity slug
                    if ($slugService) {
                        $violations = $slugService->validate($mergedParams[$varName]);
                        if (count($violations) > 0) {
                            /** @var ConstraintViolation $first */
                            $first = $violations[0];
                            $message = 'Parameter "{parameter}" for route "{route}" constraints violation: ' . $first->getMessage();
                            throw new InvalidParameterException(
                                strtr(
                                    $message,
                                    ['{parameter}' => $varName, '{route}' => $name,]
                                )
                            );
                        }
                        $varValue = $slugService->findSlug($mergedParams[$varName], true)->getSlug();
                    } // else a simple var
                    else {
                        $varValue = $mergedParams[$varName];
                    }

                    // check requirement
                    if (null !== $this->strictRequirements && !preg_match(
                            '#^' . $regexp . '$#' . (empty($token[4]) ? '' : 'u'),
                            $varValue
                        )
                    ) {
                        if ($this->strictRequirements) {
                            throw new InvalidParameterException(
                                strtr(
                                    $message,
                                    array(
                                        '{parameter}' => $varName,
                                        '{route}' => $name,
                                        '{expected}' => $regexp,
                                        '{given}' => $varValue,
                                    )
                                )
                            );
                        }

                        if ($this->logger) {
                            $this->logger->error($message, array('parameter' => $varName, 'route' => $name, 'expected' => $regexp, 'given' => $varValue));
                        }

                        return;
                    }

                    $url = $precedingChar . $varValue . $url;
                    $optional = false;
                }
            } else {
                // static text
                $url = $token[1] . $url;
                $optional = false;
            }
        }

        if ('' === $url) {
            $url = '/';
        }

        // the contexts base URL is already encoded (see Symfony\Component\HttpFoundation\Request)
        $url = strtr(rawurlencode($url), $this->decodedChars);

        // the path segments "." and ".." are interpreted as relative reference when resolving a URI; see http://tools.ietf.org/html/rfc3986#section-3.3
        // so we need to encode them as they are not used for this purpose here
        // otherwise we would generate a URI that, when followed by a user agent (e.g. browser), does not match this route
        $url = strtr($url, array('/../' => '/%2E%2E/', '/./' => '/%2E/'));
        if ('/..' === substr($url, -3)) {
            $url = substr($url, 0, -2) . '%2E%2E';
        } elseif ('/.' === substr($url, -2)) {
            $url = substr($url, 0, -1) . '%2E';
        }

        $schemeAuthority = '';
        $host = $this->context->getHost();
        $scheme = $this->context->getScheme();

        if ($requiredSchemes) {
            if (!\in_array($scheme, $requiredSchemes, true)) {
                $referenceType = self::ABSOLUTE_URL;
                $scheme = current($requiredSchemes);
            }
        }

        if ($hostTokens) {
            $routeHost = '';
            foreach ($hostTokens as $token) {
                if ('variable' === $token[0]) {
                    list ($type, $precedingChar, $regexp, $varName) = $token;
                    /** @var HostServiceInterface $hostService */
                    $hostService = $this->hostServiceManager->get($varName, $route);

                    if ($hostService) {
                        $varValue = $hostService->getHost($mergedParams[$varName]);
                    } // else a simple var
                    else {
                        $varValue = $mergedParams[$varName];
                    }

                    if (null !== $this->strictRequirements && !preg_match(
                            '#^' . $regexp . '$#i' . (empty($token[4]) ? '' : 'u'),
                            $varValue
                        )
                    ) {
                        if ($this->strictRequirements) {
                            throw new InvalidParameterException(
                                strtr(
                                    $message,
                                    array(
                                        '{parameter}' => $varName,
                                        '{route}' => $name,
                                        '{expected}' => $regexp,
                                        '{given}' => $varValue,
                                    )
                                )
                            );
                        }

                        if ($this->logger) {
                            $this->logger->error($message, array('parameter' => $varName, 'route' => $name, 'expected' => $regexp, 'given' => $varValue));
                        }

                        return;
                    }

                    $routeHost = $precedingChar . $varValue . $routeHost;
                } else {
                    $routeHost = $token[1] . $routeHost;
                }
            }

            if ($routeHost !== $host) {
                $host = $routeHost;
                if (self::ABSOLUTE_URL !== $referenceType) {
                    $referenceType = self::NETWORK_PATH;
                }
            }
        }

        if ((self::ABSOLUTE_URL === $referenceType || self::NETWORK_PATH === $referenceType) && !empty($host)) {
            $port = '';
            if ('http' === $scheme && 80 != $this->context->getHttpPort()) {
                $port = ':' . $this->context->getHttpPort();
            } elseif ('https' === $scheme && 443 != $this->context->getHttpsPort()) {
                $port = ':' . $this->context->getHttpsPort();
            }

            $schemeAuthority = self::NETWORK_PATH === $referenceType ? '//' : "$scheme://";
            $schemeAuthority .= $host . $port;
        }

        if (self::RELATIVE_PATH === $referenceType) {
            $url = self::getRelativePath($this->context->getPathInfo(), $url);
        } else {
            $url = $schemeAuthority . $this->context->getBaseUrl() . $url;
        }

        // add a query string if needed
        $extra = array_udiff_assoc(
            array_diff_key($parameters, $variables),
            $defaults,
            function ($a, $b) {
                return $a == $b ? 0 : 1;
            }
        );

        // extract fragment
        $fragment = '';
        if (isset($defaults['_fragment'])) {
            $fragment = $defaults['_fragment'];
        }

        if (isset($extra['_fragment'])) {
            $fragment = $extra['_fragment'];
            unset($extra['_fragment']);
        }

        if ($extra && $query = http_build_query($extra, '', '&', PHP_QUERY_RFC3986)) {
            // "/" and "?" can be left decoded for better user experience, see
            // http://tools.ietf.org/html/rfc3986#section-3.4
            $url .= '?' . strtr($query, array('%2F' => '/'));
        }

        if ('' !== $fragment) {
            $url .= '#' . strtr(rawurlencode($fragment), array('%2F' => '/', '%3F' => '?'));
        }

        return $url;
    }
}