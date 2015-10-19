import argparse
import json
import os
import logging

import shipping
import country
import geo
import trade

def find_closest_port(shipping_nodes, country):
    return shipping.get_nearest_port(shipping_nodes, country.lat, country.lon)

def get_all_paths(shipping_nodes, countries, trade_data, subject):
    paths = []
    from_node = find_closest_port(shipping_nodes, subject)

    node_lookup = {}
    for node in shipping_nodes:
        node_id = node['node']
        node_lookup[node_id] = node

    for c in countries:
        if c.name == subject.name:
            continue

        print c.name

        to_node = find_closest_port(shipping_nodes, c)

        try:
            short_path = shipping.get_short_path(shipping_nodes, from_node['node'], to_node['node'])
        except:
            logging.warning('could not determine path for %s' % c.name)
            continue

        if c.name in trade_data:
            # Add in values to the path
            for node in short_path:
                if 'value' not in node:
                    node['value'] = 0
                node['value'] += trade_data[c.name]['2010']

        paths.append(short_path)

    return paths

def normalise_weights(nodes, trade_data):
    total_value = float(trade_data['(Total)']['2010'])

    for node in nodes:
        node['value'] = node['value'] / total_value

def combine_nodes(all_paths):
    result = []
    nodes = {}

    for path in all_paths:
        for node in path:
            node_id = node['node']
            if node_id not in nodes:
                nodes[node_id] = True
                result.append(node)

    return result

def combine_nodes_directed(all_paths):
    result = []
    nodes = {}

    for path in all_paths:
        path_count = len(path)

        for i in range(path_count):
            node_from = path[i]
            node_to = None

            if i + 2 <= path_count:
                node_to = path[i + 1]

            node_from_id = node_from['node']

            if node_from_id not in nodes:
                node = {
                    'node': node_from_id,
                    'lat': node_from['lat'],
                    'lon': node_from['lon'],
                    'edges': [],
                    'value': node_from['value']
                }

                result.append(node)
                nodes[node_from_id] = node
            else:
                node = nodes[node_from_id]

            if node_to and node_to['node'] not in node['edges']:
                node['edges'].append(node_to['node'])

    return result

def merge_nodes(nodes):
    # FIXME: This is wrong. The remove needs to redirect parents to the new node, but not children
    node_lookup = {}

    for node in nodes:
        node_lookup[node['node']] = node

    while(True):
        to_del = []

        for nodeA_id in node_lookup:
            nodeA = node_lookup[nodeA_id]

            for nodeB_id in node_lookup:
                if nodeA_id == nodeB_id:
                    continue

                nodeB = node_lookup[nodeB_id]

                if geo.latlon_distance(nodeA['lat'], nodeA['lon'], nodeB['lat'], nodeB['lon']) < 100:
                    # Remove from node a
                    if nodeB_id in nodeA['edges']:
                        nodeA['edges'].remove(nodeB_id)

                    # Copy node b's edges from node a
                    for edge in nodeB['edges']:
                        if edge not in nodeA['edges']:
                            nodeA['edges'].append(edge)

                    # Find all nodes that have edge to node b
                    for node_id in node_lookup:
                        if node_id == nodeA_id or node_id == nodeB_id:
                            continue

                        node = node_lookup[node_id]

                        # check if the node has edge to node b
                        if nodeB_id in node['edges']:
                            # Remove the edge to node b and replace with node a
                            node['edges'].remove(nodeB_id)
                            if nodeA_id not in node['edges']:
                                node['edges'].append(nodeA_id)

                    to_del.append(nodeB_id)

            if len(to_del) > 0:
                break

        if len(to_del) == 0:
            break
        
        for node_id in to_del:
            if node_id in node_lookup:
                print('removing node %s' % node_id)
                del node_lookup[node_id]

    result = []

    for node_id in node_lookup:
        result.append(node_lookup[node_id])

    return result

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('-s', '--shipping')
    parser.add_argument('-c', '--countries')
    parser.add_argument('-d', '--tradedata')
    parser.add_argument('-o','--output', default='output.json')

    args = parser.parse_args()

    shipping_path = args.shipping
    countries_path = args.countries
    data_path = args.tradedata
    output_path = args.output

    if not os.path.exists(shipping_path):
        parser.error('Shipping path does not exist')
    if not os.path.exists(countries_path):
        parser.error('Countries path does not exist')
    if not os.path.exists(data_path):
        parser.error('Data path does not exist')

    shipping_data = shipping.read_json(shipping_path)
    nodes = shipping_data['nodes']

    trade_data = trade.read_csv(data_path)

    countries = country.read_csv(countries_path)
    subject = None

    for c in countries:
        if c.name == 'New Zealand':
            subject = c
            break

    all_paths = get_all_paths(nodes, countries, trade_data, subject)

    nodes = combine_nodes_directed(all_paths)
    normalise_weights(nodes, trade_data)
    #nodes = merge_nodes(nodes)

    shipping.write_json(output_path, nodes)

if __name__ == "__main__":
    main()